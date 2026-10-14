<?php

declare(strict_types=1);

namespace App\Domain\Integrations\MessageHandler;

use App\Domain\Integrations\DTO\SourceSyncPage;
use App\Domain\Integrations\DTO\SyncedExternalItem;
use App\Domain\Integrations\Entity\Enum\ExternalItemAvailability;
use App\Domain\Integrations\Entity\Enum\ExternalSourceSyncStatus;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Message\SyncExternalSourceMessage;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\Integrations\Service\Capability\SourceSyncIntegrationInterface;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncForbiddenException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncRateLimitedException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncUnauthorizedException;
use App\Domain\Integrations\Service\Exceptions\UnsupportedCapability;
use App\Domain\Integrations\Service\IntegrationRegistry;
use App\Domain\Integrations\Service\Suggestion\LinkSuggestionAggregator;
use App\Domain\Integrations\Service\Sync\SourceSyncIntervalPolicy;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class SyncExternalSourceHandler
{
    public const LOCK_TTL_SECONDS = 300.0;
    public const NEXT_PAGE_DELAY_MILLISECONDS = 1000;
    public const MINIMUM_RATE_LIMIT_DELAY_SECONDS = 1;

    public function __construct(
        private ExternalSourceRepository $externalSourceRepository,
        private ExternalItemRepository $externalItemRepository,
        private IntegrationRegistry $integrationRegistry,
        private SourceSyncIntervalPolicy $syncIntervalPolicy,
        private EntityManagerInterface $entityManager,
        private LockFactory $lockFactory,
        private MessageBusInterface $messageBus,
        private ClockInterface $clock,
        private LoggerInterface $logger,
        private LinkSuggestionAggregator $linkSuggestionAggregator,
    ) {
    }

    public static function lockKey(Uuid $sourceId): string
    {
        return 'integrations.sync_source.' . $sourceId->toRfc4122();
    }

    public function __invoke(SyncExternalSourceMessage $message): void
    {
        $sourceLock = $this->lockFactory->createLock(self::lockKey($message->sourceId), self::LOCK_TTL_SECONDS);
        if (!$sourceLock->acquire()) {
            $this->logger->info('External source sync skipped, another sync holds the lock', [
                'source_id' => $message->sourceId->toRfc4122(),
            ]);
            return;
        }

        try {
            $this->syncSource($message);
        } finally {
            $sourceLock->release();
        }
    }

    private function syncSource(SyncExternalSourceMessage $message): void
    {
        $source = $this->externalSourceRepository->find($message->sourceId);
        if ($source === null || !$source->isEnabled()) {
            return;
        }

        $integration = $source->getIntegration();
        if (!$integration->getStatus()->isUsable()) {
            return;
        }

        try {
            $syncCapability = $this->integrationRegistry->capability($integration, SourceSyncIntegrationInterface::class);
        } catch (UnsupportedCapability $unsupportedCapability) {
            $this->recordFailure($source, $unsupportedCapability->getMessage());
            throw new UnrecoverableMessageHandlingException($unsupportedCapability->getMessage(), 0, $unsupportedCapability);
        }

        try {
            $page = $syncCapability->fetchSourcePage($source, $source->getSyncCursor());
        } catch (SourceSyncRateLimitedException $rateLimited) {
            $this->redispatch($source, max(self::MINIMUM_RATE_LIMIT_DELAY_SECONDS, $rateLimited->getRetryAfterSeconds()) * 1000);
            return;
        } catch (SourceSyncUnauthorizedException|ReAuthenticationNeededException $unauthorized) {
            $integration->markNeedsReauth($unauthorized->getMessage());
            $this->recordFailure($source, $unauthorized->getMessage());
            throw new UnrecoverableMessageHandlingException($unauthorized->getMessage(), 401, $unauthorized);
        } catch (SourceSyncForbiddenException $forbidden) {
            $source->setEnabled(false);
            $this->recordFailure($source, $forbidden->getMessage());
            throw new UnrecoverableMessageHandlingException($forbidden->getMessage(), 403, $forbidden);
        } catch (\Throwable $failure) {
            $this->recordFailure($source, $failure->getMessage());
            throw $failure;
        }

        $this->storePage($source, $page);

        if ($page->hasMorePages) {
            $this->redispatch($source, self::NEXT_PAGE_DELAY_MILLISECONDS);
            return;
        }

        $now = $this->clock->now();
        $source->setLastSyncedAt($now);
        $source->setLastSyncStatus(ExternalSourceSyncStatus::Success);
        $source->setLastSyncError(null);
        $source->setNextSyncAt($this->syncIntervalPolicy->nextSyncAt($source, $now));
        $integration->setLastCheckedAt($now);
        $this->entityManager->flush();
    }

    private function storePage(ExternalSource $source, SourceSyncPage $page): void
    {
        $externalIds = array_map(
            static fn (SyncedExternalItem $syncedItem): string => $syncedItem->externalId,
            $page->items,
        );
        $existingItems = $this->externalItemRepository->findBySourceIndexedByExternalId($source, $externalIds);
        $seenAt = $this->clock->now();

        foreach ($page->items as $syncedItem) {
            $externalItem = $existingItems[$syncedItem->externalId] ?? null;
            if ($externalItem === null) {
                $externalItem = new ExternalItem();
                $externalItem->setExternalId($syncedItem->externalId);
                $source->addItem($externalItem);
                $this->entityManager->persist($externalItem);
                $existingItems[$syncedItem->externalId] = $externalItem;
            }

            $externalItem
                ->setKind($syncedItem->kind)
                ->setTitle($syncedItem->title)
                ->setExcerpt($syncedItem->excerpt)
                ->setAuthor($syncedItem->author)
                ->setUrl($syncedItem->url)
                ->setParentExternalId($syncedItem->parentExternalId)
                ->setExternalUpdatedAt($syncedItem->externalUpdatedAt)
                ->setRaw($syncedItem->raw)
                ->setLastSeenAt($seenAt)
                ->setAvailability($syncedItem->availability);
        }

        $this->markMissing($source, $page->missingExternalIds);

        $source->setSyncCursor($page->nextCursor);
        $this->entityManager->flush();

        $this->linkSuggestionAggregator->refresh($source->getIntegration()->getLarp(), $existingItems);
    }

    /**
     * @param list<string> $missingExternalIds
     */
    private function markMissing(ExternalSource $source, array $missingExternalIds): void
    {
        foreach ($this->externalItemRepository->findBySourceIndexedByExternalId($source, $missingExternalIds) as $missingItem) {
            $missingItem->setAvailability(ExternalItemAvailability::Gone);
        }
    }

    private function redispatch(ExternalSource $source, int $delayMilliseconds): void
    {
        $this->messageBus->dispatch(
            new SyncExternalSourceMessage($source->getId()),
            [new DelayStamp($delayMilliseconds)],
        );
    }

    private function recordFailure(ExternalSource $source, string $error): void
    {
        $source->setLastSyncStatus(ExternalSourceSyncStatus::Failed);
        $source->setLastSyncError($error);
        $this->entityManager->flush();

        $this->logger->warning('External source sync failed', [
            'source_id' => $source->getId()->toRfc4122(),
            'error' => $error,
        ]);
    }
}
