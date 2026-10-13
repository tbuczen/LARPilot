<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations;

use App\Domain\Integrations\DTO\SourceSyncPage;
use App\Domain\Integrations\DTO\SyncedExternalItem;
use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceSyncStatus;
use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Message\EnqueueDueSourceSyncsMessage;
use App\Domain\Integrations\Message\SyncExternalSourceMessage;
use App\Domain\Integrations\MessageHandler\EnqueueDueSourceSyncsHandler;
use App\Domain\Integrations\MessageHandler\SyncExternalSourceHandler;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\Integrations\Schedule\IntegrationSyncSchedule;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncForbiddenException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncRateLimitedException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncUnauthorizedException;
use App\Domain\Integrations\Service\IntegrationRegistry;
use App\Domain\Integrations\Service\Sync\SourceSyncIntervalPolicy;
use App\Domain\Integrations\Service\Sync\SourceSyncRequesterInterface;
use App\Domain\Integrations\Service\Sync\SyncNowDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Tests\Support\Factory\Integrations\ExternalItemFactory;
use Tests\Support\Factory\Integrations\ExternalSourceFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Integrations\FakeSourceSyncCapability;

class BackgroundSyncCest
{
    private const NOW = '2026-10-12 12:00:00';

    public function _before(FunctionalTester $I): void
    {
        $this->integrationsTransport($I)->reset();
    }

    public function syncNowFetchesEveryPageAndStoresItems(FunctionalTester $I): void
    {
        $I->wantTo('sync a source end to end: dispatch, fetch every page, upsert items and finish the run');

        $source = $this->createDiscordSource();
        ExternalItemFactory::new([
            'source' => $source,
            'externalId' => 'thread-a',
            'kind' => ExternalItemKind::Thread,
            'title' => 'Old title',
        ])->create();

        $fakeCapability = new FakeSourceSyncCapability(
            new SourceSyncPage(
                [
                    new SyncedExternalItem('thread-a', ExternalItemKind::Thread, 'Renamed thread', 'First excerpt', 'Ada', 'https://discord.test/a'),
                    new SyncedExternalItem('thread-b', ExternalItemKind::Thread, 'Second thread'),
                ],
                ['after' => 'thread-b'],
                true,
            ),
            new SourceSyncPage(
                [new SyncedExternalItem('message-c', ExternalItemKind::Message, 'Reply', parentExternalId: 'thread-b')],
                ['after' => 'message-c'],
                false,
            ),
        );

        $I->assertTrue($this->createSyncNowDispatcher($I, $fakeCapability)->dispatch($source->_real()));

        $handledEnvelopes = $this->drainIntegrationsTransport($I, $this->createSyncHandler($I, $fakeCapability));

        $I->assertCount(2, $handledEnvelopes);
        $I->assertSame([], $handledEnvelopes[0]->all(DelayStamp::class));
        $I->assertEquals(
            new DelayStamp(SyncExternalSourceHandler::NEXT_PAGE_DELAY_MILLISECONDS),
            $handledEnvelopes[1]->last(DelayStamp::class),
        );
        $I->assertSame([null, ['after' => 'thread-b']], $fakeCapability->requestedCursors());

        $syncedSource = $this->reloadSource($I, $source->_real());
        $itemsByExternalId = $this->itemsByExternalId($I, $syncedSource);
        $I->assertSame(['message-c', 'thread-a', 'thread-b'], array_keys($itemsByExternalId));
        $I->assertSame('Renamed thread', $itemsByExternalId['thread-a']->getTitle());
        $I->assertSame('Ada', $itemsByExternalId['thread-a']->getAuthor());
        $I->assertSame('thread-b', $itemsByExternalId['message-c']->getParentExternalId());
        $I->assertEquals(new \DateTimeImmutable(self::NOW), $itemsByExternalId['thread-b']->getLastSeenAt());

        $I->assertSame(['after' => 'message-c'], $syncedSource->getSyncCursor());
        $I->assertSame(ExternalSourceSyncStatus::Success, $syncedSource->getLastSyncStatus());
        $I->assertNull($syncedSource->getLastSyncError());
        $I->assertEquals(new \DateTimeImmutable(self::NOW), $syncedSource->getLastSyncedAt());
        $I->assertEquals(new \DateTimeImmutable('2026-10-12 12:30:00'), $syncedSource->getNextSyncAt());
        $I->assertEquals(new \DateTimeImmutable(self::NOW), $syncedSource->getIntegration()->getLastCheckedAt());
    }

    public function syncNowDispatcherIsTheHubSyncRequester(FunctionalTester $I): void
    {
        $I->wantTo('see the hub sync requester queue a sync of the source');

        $source = $this->createDiscordSource();
        $syncRequester = $this->createSyncNowDispatcher($I, new FakeSourceSyncCapability());
        $I->assertInstanceOf(SourceSyncRequesterInterface::class, $syncRequester);

        $syncRequester->requestSync($source->_real());

        $sentEnvelopes = $this->integrationsTransport($I)->getSent();
        $I->assertCount(1, $sentEnvelopes);
        $I->assertTrue($source->getId()->equals($sentEnvelopes[0]->getMessage()->sourceId));
        $I->assertInstanceOf(SyncNowDispatcher::class, $I->grabService(SourceSyncRequesterInterface::class));
    }

    public function nextRunResumesFromThePersistedCursor(FunctionalTester $I): void
    {
        $I->wantTo('see a sync resume from the cursor stored by the previous run');

        $source = $this->createDiscordSource(['syncCursor' => ['after' => 'thread-z']]);
        $fakeCapability = new FakeSourceSyncCapability(new SourceSyncPage([], ['after' => 'thread-z'], false));

        $this->createSyncHandler($I, $fakeCapability)(new SyncExternalSourceMessage($source->getId()));

        $I->assertSame([['after' => 'thread-z']], $fakeCapability->requestedCursors());
        $I->assertSame(['after' => 'thread-z'], $this->reloadSource($I, $source->_real())->getSyncCursor());
    }

    public function rateLimitedSyncIsRedispatchedWithRetryAfterDelay(FunctionalTester $I): void
    {
        $I->wantTo('see a 429 from the provider re-queue the sync after the provider retry-after delay');

        $source = $this->createDiscordSource(['syncCursor' => ['after' => 'thread-a']]);
        $fakeCapability = new FakeSourceSyncCapability(new SourceSyncRateLimitedException(42));

        $this->createSyncHandler($I, $fakeCapability)(new SyncExternalSourceMessage($source->getId()));

        $sentEnvelopes = $this->integrationsTransport($I)->getSent();
        $I->assertCount(1, $sentEnvelopes);
        $I->assertInstanceOf(SyncExternalSourceMessage::class, $sentEnvelopes[0]->getMessage());
        $I->assertTrue($source->getId()->equals($sentEnvelopes[0]->getMessage()->sourceId));
        $I->assertEquals(new DelayStamp(42000), $sentEnvelopes[0]->last(DelayStamp::class));

        $rateLimitedSource = $this->reloadSource($I, $source->_real());
        $I->assertSame(['after' => 'thread-a'], $rateLimitedSource->getSyncCursor());
        $I->assertNull($rateLimitedSource->getLastSyncStatus());
        $I->assertSame(IntegrationStatus::Active, $rateLimitedSource->getIntegration()->getStatus());
    }

    public function unauthorizedSyncMarksIntegrationForReauthentication(FunctionalTester $I): void
    {
        $I->wantTo('see a 401 from the provider mark the integration as needing re-authentication');

        $source = $this->createDiscordSource();
        $handler = $this->createSyncHandler($I, new FakeSourceSyncCapability(new SourceSyncUnauthorizedException('401 Unauthorized')));

        $I->expectThrowable(
            UnrecoverableMessageHandlingException::class,
            static fn () => $handler(new SyncExternalSourceMessage($source->getId())),
        );

        $failedSource = $this->reloadSource($I, $source->_real());
        $I->assertSame(IntegrationStatus::NeedsReauth, $failedSource->getIntegration()->getStatus());
        $I->assertSame('401 Unauthorized', $failedSource->getIntegration()->getLastError());
        $I->assertSame(ExternalSourceSyncStatus::Failed, $failedSource->getLastSyncStatus());
        $I->assertTrue($failedSource->isEnabled());
        $I->assertSame([], $this->integrationsTransport($I)->getSent());
    }

    public function expiredCredentialsMarkIntegrationForReauthentication(FunctionalTester $I): void
    {
        $I->wantTo('see a credential provider asking for re-authentication stop the sync for good');

        $source = $this->createDiscordSource();
        $integrationId = $source->getIntegration()->getId()->toRfc4122();
        $handler = $this->createSyncHandler($I, new FakeSourceSyncCapability(new ReAuthenticationNeededException($integrationId)));

        $I->expectThrowable(
            UnrecoverableMessageHandlingException::class,
            static fn () => $handler(new SyncExternalSourceMessage($source->getId())),
        );

        $I->assertSame(IntegrationStatus::NeedsReauth, $this->reloadSource($I, $source->_real())->getIntegration()->getStatus());
    }

    public function forbiddenSyncDisablesTheSource(FunctionalTester $I): void
    {
        $I->wantTo('see a 403 from the provider disable the source without touching the integration');

        $source = $this->createDiscordSource();
        $handler = $this->createSyncHandler($I, new FakeSourceSyncCapability(new SourceSyncForbiddenException()));

        $I->expectThrowable(
            UnrecoverableMessageHandlingException::class,
            static fn () => $handler(new SyncExternalSourceMessage($source->getId())),
        );

        $failedSource = $this->reloadSource($I, $source->_real());
        $I->assertFalse($failedSource->isEnabled());
        $I->assertSame(ExternalSourceSyncStatus::Failed, $failedSource->getLastSyncStatus());
        $I->assertSame(IntegrationStatus::Active, $failedSource->getIntegration()->getStatus());
    }

    public function heldLockPreventsConcurrentSyncOfTheSameSource(FunctionalTester $I): void
    {
        $I->wantTo('see a second sync of a source skip while another sync holds its lock');

        $source = $this->createDiscordSource();
        $fakeCapability = new FakeSourceSyncCapability(new SourceSyncPage([], null, false));
        $handler = $this->createSyncHandler($I, $fakeCapability);

        /** @var LockFactory $lockFactory */
        $lockFactory = $I->grabService(LockFactory::class);
        $runningSyncLock = $lockFactory->createLock(SyncExternalSourceHandler::lockKey($source->getId()));
        $I->assertTrue($runningSyncLock->acquire());

        try {
            $handler(new SyncExternalSourceMessage($source->getId()));
        } finally {
            $runningSyncLock->release();
        }

        $I->assertSame([], $fakeCapability->requestedCursors());
        $I->assertNull($this->reloadSource($I, $source->_real())->getLastSyncStatus());

        $handler(new SyncExternalSourceMessage($source->getId()));

        $I->assertSame([null], $fakeCapability->requestedCursors());
    }

    public function syncNowRefusesDisabledSourcesAndUnusableIntegrations(FunctionalTester $I): void
    {
        $I->wantTo('see "sync now" refuse sources that cannot be synced');

        $syncNowDispatcher = $this->createSyncNowDispatcher($I, new FakeSourceSyncCapability());

        $disabledSource = $this->createDiscordSource(['enabled' => false]);
        $sourceOfReauthIntegration = ExternalSourceFactory::new([
            'integration' => LarpIntegrationFactory::new()->discord()->needsReauth(),
            'kind' => ExternalSourceKind::DiscordForum,
        ])->create();

        $I->assertFalse($syncNowDispatcher->dispatch($disabledSource->_real()));
        $I->assertFalse($syncNowDispatcher->dispatch($sourceOfReauthIntegration->_real()));
        $I->assertFalse($syncNowDispatcher->dispatch(ExternalSourceFactory::createOne()->_real()));
        $I->assertSame([], $this->integrationsTransport($I)->getSent());
    }

    public function scheduleEnqueuesOnlyDueSources(FunctionalTester $I): void
    {
        $I->wantTo('see the integration schedule enqueue enabled, syncable sources whose next sync time has passed');

        $neverSyncedSource = $this->createDiscordSource();
        $overdueSource = $this->createDiscordSource(['nextSyncAt' => new \DateTimeImmutable('2026-10-12 11:55:00')]);
        $notYetDueSource = $this->createDiscordSource(['nextSyncAt' => new \DateTimeImmutable('2026-10-12 12:10:00')]);
        $this->createDiscordSource(['enabled' => false]);
        ExternalSourceFactory::new([
            'integration' => LarpIntegrationFactory::new()->discord()->needsReauth(),
            'kind' => ExternalSourceKind::DiscordForum,
        ])->create();
        ExternalSourceFactory::createOne();

        $fakeCapability = new FakeSourceSyncCapability();
        $enqueueHandler = new EnqueueDueSourceSyncsHandler(
            $I->grabService(ExternalSourceRepository::class),
            $this->createSyncNowDispatcher($I, $fakeCapability),
            $this->createRegistry($fakeCapability),
            new SourceSyncIntervalPolicy(),
            $I->grabService(EntityManagerInterface::class),
            new MockClock(self::NOW),
        );

        $I->assertSame(2, $enqueueHandler(new EnqueueDueSourceSyncsMessage()));

        $enqueuedSourceIds = array_map(
            static fn (Envelope $envelope): string => $envelope->getMessage()->sourceId->toRfc4122(),
            $this->integrationsTransport($I)->getSent(),
        );
        sort($enqueuedSourceIds);
        $expectedSourceIds = [$neverSyncedSource->getId()->toRfc4122(), $overdueSource->getId()->toRfc4122()];
        sort($expectedSourceIds);
        $I->assertSame($expectedSourceIds, $enqueuedSourceIds);

        $I->assertEquals(new \DateTimeImmutable('2026-10-12 12:30:00'), $this->reloadSource($I, $overdueSource->_real())->getNextSyncAt());
        $I->assertEquals(new \DateTimeImmutable('2026-10-12 12:10:00'), $this->reloadSource($I, $notYetDueSource->_real())->getNextSyncAt());

        $I->assertSame(0, $enqueueHandler(new EnqueueDueSourceSyncsMessage()));
    }

    public function integrationScheduleRunsEveryFiveMinutes(FunctionalTester $I): void
    {
        $I->wantTo('see the integrations schedule enqueue due sources every 5 minutes');

        /** @var IntegrationSyncSchedule $integrationSyncSchedule */
        $integrationSyncSchedule = $I->grabService(IntegrationSyncSchedule::class);
        $recurringMessages = $integrationSyncSchedule->getSchedule()->getRecurringMessages();

        $I->assertCount(1, $recurringMessages);
        $I->assertSame('every 5 minutes', (string) $recurringMessages[0]->getTrigger());
        $scheduledMessages = iterator_to_array($recurringMessages[0]->getProvider()->getMessages(new MessageContext(
            IntegrationSyncSchedule::NAME,
            $recurringMessages[0]->getId(),
            $recurringMessages[0]->getTrigger(),
            new \DateTimeImmutable(self::NOW),
        )), false);
        $I->assertCount(1, $scheduledMessages);
        $I->assertInstanceOf(EnqueueDueSourceSyncsMessage::class, $scheduledMessages[0]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createDiscordSource(array $attributes = []): mixed
    {
        return ExternalSourceFactory::new(array_merge([
            'integration' => LarpIntegrationFactory::new()->discord()->botInstall('guild-' . bin2hex(random_bytes(4))),
            'kind' => ExternalSourceKind::DiscordForum,
        ], $attributes))->create();
    }

    private function createSyncHandler(FunctionalTester $I, FakeSourceSyncCapability $fakeCapability): SyncExternalSourceHandler
    {
        return new SyncExternalSourceHandler(
            $I->grabService(ExternalSourceRepository::class),
            $I->grabService(ExternalItemRepository::class),
            $this->createRegistry($fakeCapability),
            new SourceSyncIntervalPolicy(),
            $I->grabService(EntityManagerInterface::class),
            $I->grabService(LockFactory::class),
            $I->grabService(MessageBusInterface::class),
            new MockClock(self::NOW),
            new NullLogger(),
        );
    }

    private function createSyncNowDispatcher(FunctionalTester $I, FakeSourceSyncCapability $fakeCapability): SyncNowDispatcher
    {
        return new SyncNowDispatcher($I->grabService(MessageBusInterface::class), $this->createRegistry($fakeCapability));
    }

    private function createRegistry(FakeSourceSyncCapability $fakeCapability): IntegrationRegistry
    {
        return new IntegrationRegistry(new ServiceLocator(
            [FakeSourceSyncCapability::providerKey() => static fn (): FakeSourceSyncCapability => $fakeCapability],
            [FakeSourceSyncCapability::providerKey() => FakeSourceSyncCapability::class],
        ));
    }

    /**
     * @return list<Envelope>
     */
    private function drainIntegrationsTransport(FunctionalTester $I, SyncExternalSourceHandler $handler): array
    {
        $transport = $this->integrationsTransport($I);
        $handledEnvelopes = [];

        while (($nextEnvelope = $transport->getSent()[count($handledEnvelopes)] ?? null) !== null) {
            $handledEnvelopes[] = $nextEnvelope;
            $handler($nextEnvelope->getMessage());
        }

        return $handledEnvelopes;
    }

    private function integrationsTransport(FunctionalTester $I): InMemoryTransport
    {
        return $I->grabService('messenger.transport.integrations');
    }

    private function reloadSource(FunctionalTester $I, ExternalSource $source): ExternalSource
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->clear();

        return $entityManager->find(ExternalSource::class, $source->getId());
    }

    /**
     * @return array<string, ExternalItem>
     */
    private function itemsByExternalId(FunctionalTester $I, ExternalSource $source): array
    {
        /** @var ExternalItemRepository $externalItemRepository */
        $externalItemRepository = $I->grabService(ExternalItemRepository::class);
        $itemsByExternalId = [];
        foreach ($externalItemRepository->findBy(['source' => $source]) as $item) {
            $itemsByExternalId[$item->getExternalId()] = $item;
        }
        ksort($itemsByExternalId);

        return $itemsByExternalId;
    }
}
