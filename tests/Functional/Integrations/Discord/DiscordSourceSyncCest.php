<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Discord;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\Integrations\Discord\Content\DiscordThreadDigestProvider;
use App\Domain\Integrations\Entity\Enum\ExternalItemAvailability;
use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceSyncStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Message\SyncExternalSourceMessage;
use App\Domain\Integrations\MessageHandler\SyncExternalSourceHandler;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\Integrations\Service\Capability\ConversationSourceIntegrationInterface;
use App\Domain\Integrations\Service\IntegrationRegistry;
use App\Domain\Integrations\Service\Suggestion\LinkSuggestionAggregator;
use App\Domain\Integrations\Service\Sync\SourceSyncIntervalPolicy;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\ExternalItemFactory;
use Tests\Support\Factory\Integrations\ExternalSourceFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Http\FixtureRouter;
use Tests\Support\Http\IntegrationFixtureRouters;

class DiscordSourceSyncCest
{
    private const NOW = '2026-10-12 12:00:00';
    private const GUILD_ID = IntegrationFixtureRouters::DISCORD_GUILD_ID;
    private const FORUM_CHANNEL_ID = IntegrationFixtureRouters::DISCORD_FORUM_CHANNEL_ID;
    private const SEAL_THREAD_ID = IntegrationFixtureRouters::DISCORD_THREAD_ID;
    private const DEBTS_THREAD_ID = '1100000000000000302';
    private const PROLOGUE_THREAD_ID = '1100000000000000290';
    private const DISCARDED_THREAD_ID = '1100000000000000289';
    private const EMPIRE_THREAD_ID = '1100000000000000288';
    private const COUNCIL_THREAD_ID = '1100000000000000310';
    private const MAIN_PLOT_FORUM_TAG_ID = '1100000000000000901';
    private const APPROVED_FORUM_TAG_ID = '1100000000000000903';
    private const OLDEST_FIRST_PAGE_ARCHIVE_TIMESTAMP = '2026-07-30T08:00:00.000+00:00';

    public function _before(FunctionalTester $I): void
    {
        $this->integrationsTransport($I)->reset();
        $this->discordRouter($I)
            ->on('GET', '/guilds/{guild_id}/channels', 'channels.json')
            ->on('GET', '/guilds/{guild_id}/threads/active', 'threads_active.json')
            ->on('GET', '/channels/{channel_id}/threads/archived/public', 'threads_archived_public.json')
            ->on('GET', '/channels/{channel_id}/threads/archived/public?before=' . rawurlencode(self::OLDEST_FIRST_PAGE_ARCHIVE_TIMESTAMP), 'threads_archived_public_last_page.json')
            ->on('GET', '/channels/{channel_id}/messages', 'messages.json');
    }

    public function firstSyncStoresWatchedThreadsWithTagsAndExcerpts(FunctionalTester $I): void
    {
        $I->wantTo('sync a watched forum and store its posts with mapped tags, activity and a sanitized excerpt');

        $source = $this->createForumSource($I, 'organizer_only');

        $this->syncSource($I, $source);

        $syncedSource = $this->reloadSource($I, $source);
        $I->assertSame(ExternalSourceSyncStatus::Success, $syncedSource->getLastSyncStatus());
        $I->assertTrue($syncedSource->isEnabled());

        $items = $this->itemsByExternalId($I, $syncedSource);
        $I->assertSame(
            [self::EMPIRE_THREAD_ID, self::DISCARDED_THREAD_ID, self::PROLOGUE_THREAD_ID, self::SEAL_THREAD_ID, self::DEBTS_THREAD_ID],
            array_map(strval(...), array_keys($items)),
        );
        $I->assertArrayNotHasKey(self::COUNCIL_THREAD_ID, $items);

        $sealPost = $items[self::SEAL_THREAD_ID];
        $I->assertSame(ExternalItemKind::ForumPost, $sealPost->getKind());
        $I->assertSame(ExternalItemAvailability::Ok, $sealPost->getAvailability());
        $I->assertSame('Lady Morwen — the stolen seal', $sealPost->getTitle());
        $I->assertSame('https://discord.com/channels/' . self::GUILD_ID . '/' . self::SEAL_THREAD_ID, $sealPost->getUrl());
        $I->assertSame(self::FORUM_CHANNEL_ID, $sealPost->getParentExternalId());
        $I->assertSame("Morwen's Writer", $sealPost->getAuthor());
        $I->assertNotNull($sealPost->getExternalUpdatedAt());

        $raw = $sealPost->getRaw();
        $I->assertSame(3, $raw['messageCount']);
        $I->assertSame('1300000000000000003', $raw['lastMessageId']);
        $I->assertSame('character', $raw['targetType']);
        $I->assertEquals([
            ['forumTagId' => self::MAIN_PLOT_FORUM_TAG_ID, 'name' => 'Main plot', 'tagId' => $raw['appliedTags'][0]['tagId'], 'targetType' => 'character'],
            ['forumTagId' => self::APPROVED_FORUM_TAG_ID, 'name' => 'Approved', 'tagId' => null, 'targetType' => null],
        ], $raw['appliedTags']);
        $I->assertNotNull($raw['appliedTags'][0]['tagId']);

        $excerpt = (string) $sealPost->getExcerpt();
        $I->assertStringStartsWith('Lady Morwen wants the stolen seal back before the council convenes.', $excerpt);
        $I->assertStringContainsString("Morwen's Writer: @Aldric (GM) who holds the seal at game start? See the map in #channel.", $excerpt);
        $I->assertStringContainsString('Aldric (GM): Reply: the seal should surface during the second evening.', $excerpt);
        $I->assertStringNotContainsString('**', $excerpt);
        $I->assertStringNotContainsString('<@', $excerpt);
        $I->assertLessThanOrEqual(500, mb_strlen($excerpt));

        $I->assertSame(ExternalItemAvailability::Archived, $items[self::PROLOGUE_THREAD_ID]->getAvailability());
        $I->assertSame(ExternalItemAvailability::Archived, $items[self::EMPIRE_THREAD_ID]->getAvailability());
        $I->assertEquals(['archivedThrough' => '2026-08-15T21:10:00.000000+00:00', 'backfillBefore' => null, 'backfillNewest' => null], $syncedSource->getSyncCursor());
    }

    public function secondRunWithNoChangesMakesNoMessageHistoryCalls(FunctionalTester $I): void
    {
        $I->wantTo('see an unchanged source synced again without reading any message history');

        $source = $this->createForumSource($I, 'organizer_only');
        $this->syncSource($I, $source);
        $firstRunExcerpt = $this->itemsByExternalId($I, $this->reloadSource($I, $source))[self::SEAL_THREAD_ID]->getExcerpt();

        $requestsBefore = count($this->discordRouter($I)->getRequests());
        $this->syncSource($I, $source);
        $secondRunRequests = $this->requestSummaries($I, $requestsBefore);

        $I->assertSame([], array_values(array_filter($secondRunRequests, static fn (string $request): bool => str_ends_with($request, '/messages'))));
        $I->assertSame($firstRunExcerpt, $this->itemsByExternalId($I, $this->reloadSource($I, $source))[self::SEAL_THREAD_ID]->getExcerpt());
    }

    public function archiveCursorStopsPaging(FunctionalTester $I): void
    {
        $I->wantTo('page back through archived threads only until the stored archive cursor');

        $source = $this->createForumSource($I, 'organizer_only');
        $archivedPath = 'GET /channels/' . self::FORUM_CHANNEL_ID . '/threads/archived/public';

        $requestsBefore = count($this->discordRouter($I)->getRequests());
        $this->syncSource($I, $source);
        $firstRunArchivedRequests = $this->matchingRequests($this->discordRouter($I), $requestsBefore, $archivedPath);
        $I->assertCount(2, $firstRunArchivedRequests);
        $I->assertArrayNotHasKey('before', $firstRunArchivedRequests[0]['query']);
        $I->assertSame(self::OLDEST_FIRST_PAGE_ARCHIVE_TIMESTAMP, $firstRunArchivedRequests[1]['query']['before']);

        $requestsBefore = count($this->discordRouter($I)->getRequests());
        $this->syncSource($I, $source);
        $secondRunArchivedRequests = $this->matchingRequests($this->discordRouter($I), $requestsBefore, $archivedPath);
        $I->assertCount(1, $secondRunArchivedRequests);
        $I->assertArrayNotHasKey('before', $secondRunArchivedRequests[0]['query']);
    }

    public function changedThreadReadsOnlyMessagesAfterItsCursor(FunctionalTester $I): void
    {
        $I->wantTo('read only the new messages of a thread whose last message changed and mark vanished threads as gone');

        $source = $this->createForumSource($I, 'organizer_only');
        $this->syncSource($I, $source);

        $this->discordRouter($I)
            ->on('GET', '/guilds/{guild_id}/threads/active', 'threads_active_after_reply.json')
            ->on('GET', '/channels/{channel_id}/messages?after=1300000000000000003', 'messages_after_cursor.json');
        $requestsBefore = count($this->discordRouter($I)->getRequests());
        $this->syncSource($I, $source);

        $messageRequests = $this->matchingRequests($this->discordRouter($I), $requestsBefore, 'GET /channels/' . self::SEAL_THREAD_ID . '/messages');
        $I->assertCount(1, $messageRequests);
        $I->assertSame('1300000000000000003', $messageRequests[0]['query']['after']);
        $I->assertSame([], array_values(array_filter(
            $this->requestSummaries($I, $requestsBefore),
            static fn (string $request): bool => str_ends_with($request, '/messages') && !str_contains($request, self::SEAL_THREAD_ID),
        )));

        $items = $this->itemsByExternalId($I, $this->reloadSource($I, $source));
        $sealExcerpt = (string) $items[self::SEAL_THREAD_ID]->getExcerpt();
        $I->assertStringStartsWith('Lady Morwen wants the stolen seal back', $sealExcerpt);
        $I->assertStringContainsString("Aldric (GM): @Morwen's Writer the smugglers want payment in silver before", $sealExcerpt);
        $I->assertSame(4, $items[self::SEAL_THREAD_ID]->getRaw()['messageCount']);
        $I->assertSame(ExternalItemAvailability::Gone, $items[self::DEBTS_THREAD_ID]->getAvailability());
        $I->assertSame(ExternalItemAvailability::Archived, $items[self::PROLOGUE_THREAD_ID]->getAvailability());
    }

    public function playerFacingSourcesStoreNoExcerpts(FunctionalTester $I): void
    {
        $I->wantTo('sync a player-facing source as metadata only, without reading messages or keeping old excerpts');

        $source = $this->createForumSource($I, 'player_facing');
        ExternalItemFactory::new([
            'source' => $source,
            'externalId' => '1100000000000000100',
            'kind' => ExternalItemKind::ForumPost,
            'title' => 'Synced while organizer-only',
            'excerpt' => 'Secret plot details',
            'author' => 'Morwen',
            'availability' => ExternalItemAvailability::Archived,
        ])->create();

        $requestsBefore = count($this->discordRouter($I)->getRequests());
        $this->syncSource($I, $source);

        $I->assertSame([], array_values(array_filter(
            $this->requestSummaries($I, $requestsBefore),
            static fn (string $request): bool => str_ends_with($request, '/messages'),
        )));

        $items = $this->itemsByExternalId($I, $this->reloadSource($I, $source));
        $I->assertCount(6, $items);
        foreach ($items as $item) {
            $I->assertNull($item->getExcerpt());
            $I->assertNull($item->getAuthor());
            $I->assertArrayNotHasKey('messages', $item->getRaw() ?? []);
        }

        $I->assertSame('Lady Morwen — the stolen seal', $items[self::SEAL_THREAD_ID]->getTitle());
        $I->assertSame(3, $items[self::SEAL_THREAD_ID]->getRaw()['messageCount']);
        $I->assertSame(ExternalItemAvailability::Archived, $items['1100000000000000100']->getAvailability());
    }

    public function forbiddenDisablesTheSourceAfterASingleRequest(FunctionalTester $I): void
    {
        $I->wantTo('disable a source the bot can no longer read after one forbidden request and never retry it');

        $source = $this->createForumSource($I, 'organizer_only');
        $this->discordRouter($I)->on('GET', '/guilds/{guild_id}/threads/active', 'missing_access.json', 403);
        $requestsBefore = count($this->discordRouter($I)->getRequests());

        $I->expectThrowable(UnrecoverableMessageHandlingException::class, function () use ($I, $source): void {
            $this->createSyncHandler($I)(new SyncExternalSourceMessage($source->getId()));
        });

        $I->assertSame(['GET /guilds/' . self::GUILD_ID . '/threads/active'], $this->requestSummaries($I, $requestsBefore));
        $disabledSource = $this->reloadSource($I, $source);
        $I->assertFalse($disabledSource->isEnabled());
        $I->assertSame(ExternalSourceSyncStatus::Failed, $disabledSource->getLastSyncStatus());
        $I->assertCount(0, $this->integrationsTransport($I)->getSent());

        $this->createSyncHandler($I)(new SyncExternalSourceMessage($source->getId()));
        $I->assertCount(1, array_slice($this->discordRouter($I)->getRequests(), $requestsBefore));
    }

    public function rateLimitedSyncIsRequeuedWithoutRetryingInProcess(FunctionalTester $I): void
    {
        $I->wantTo('requeue a rate-limited sync after Discord\'s retry_after instead of retrying in the worker');

        $source = $this->createForumSource($I, 'organizer_only');
        $this->discordRouter($I)->on('GET', '/guilds/{guild_id}/threads/active', 'rate_limited.json', 429, IntegrationFixtureRouters::DISCORD_RATE_LIMIT_HEADERS);
        $requestsBefore = count($this->discordRouter($I)->getRequests());

        $this->createSyncHandler($I)(new SyncExternalSourceMessage($source->getId()));

        $I->assertSame(['GET /guilds/' . self::GUILD_ID . '/threads/active'], $this->requestSummaries($I, $requestsBefore));
        $requeuedEnvelopes = $this->integrationsTransport($I)->getSent();
        $I->assertCount(1, $requeuedEnvelopes);
        $I->assertEquals(new DelayStamp(2000), $requeuedEnvelopes[0]->last(DelayStamp::class));
        $I->assertTrue($this->reloadSource($I, $source)->isEnabled());
        $I->assertCount(0, $this->itemsByExternalId($I, $this->reloadSource($I, $source)));
    }

    public function digestListsTheThreadMessagesInOrder(FunctionalTester $I): void
    {
        $I->wantTo('read a synced thread as a digest with title, tags and ordered message chunks');

        $source = $this->createForumSource($I, 'organizer_only');
        $this->syncSource($I, $source);
        $sealPost = $this->itemsByExternalId($I, $this->reloadSource($I, $source))[self::SEAL_THREAD_ID];

        $digestProvider = new DiscordThreadDigestProvider($I->grabService(ExternalItemRepository::class));
        $reference = $this->referenceTo($sealPost);
        $I->assertTrue($digestProvider->supports($reference));

        $digest = $digestProvider->fetchContent($reference);

        $I->assertSame('Lady Morwen — the stolen seal', $digest->title);
        $I->assertSame(['Main plot', 'Approved'], $digest->tags);
        $I->assertCount(3, $digest->chunks);
        $I->assertSame([0, 1, 2], array_map(static fn ($chunk): int => $chunk->position, $digest->chunks));
        $I->assertSame(["Morwen's Writer", "Morwen's Writer", 'Aldric (GM)'], array_map(static fn ($chunk): ?string => $chunk->author, $digest->chunks));
        $I->assertSame("Lady Morwen wants the stolen seal back before the council convenes.\n- Suspects her cousin\n- Owes a favour to the smugglers", $digest->chunks[0]->text);
        $I->assertEquals(new \DateTimeImmutable('2026-09-20T18:00:00+00:00'), $digest->chunks[0]->postedAt);
        $I->assertSame(
            'https://discord.com/channels/' . self::GUILD_ID . '/' . self::SEAL_THREAD_ID . '/1300000000000000003',
            $digest->chunks[2]->permalink,
        );
        $I->assertEquals($sealPost->getExternalUpdatedAt(), $digest->externalUpdatedAt);
        $I->assertSame(64, strlen($digest->contentHash));

        $googleReference = $this->referenceTo($sealPost);
        $googleReference->setProvider(LarpIntegrationProvider::Google);
        $I->assertFalse($digestProvider->supports($googleReference));
    }

    public function conversationCapabilityListsTheChannelThreads(FunctionalTester $I): void
    {
        $I->wantTo('list the public threads of a Discord channel through the conversation capability');

        $source = $this->createForumSource($I, 'organizer_only');
        $provider = $I->grabService(IntegrationRegistry::class)->capability($source->getIntegration(), ConversationSourceIntegrationInterface::class);

        $threadIds = array_map(
            static fn ($thread): string => $thread->externalId,
            $provider->listThreads($source->getIntegration(), self::FORUM_CHANNEL_ID),
        );

        $I->assertSame([self::SEAL_THREAD_ID, self::DEBTS_THREAD_ID, self::PROLOGUE_THREAD_ID, self::DISCARDED_THREAD_ID, self::EMPIRE_THREAD_ID], $threadIds);
    }

    private function createForumSource(FunctionalTester $I, string $visibility): ExternalSource
    {
        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = LarpIntegrationFactory::new()
            ->forLarp($larp)
            ->createdBy($organizer)
            ->discord()
            ->botInstall(self::GUILD_ID)
            ->create();

        $entityManager = $I->getEntityManager();
        $tag = new Tag();
        $tag->setLarp($entityManager->find(Larp::class, $larp->getId()));
        $tag->setCreatedBy($entityManager->find(User::class, $organizer->getId()));
        $tag->setTitle('Main plot');
        $entityManager->persist($tag);
        $entityManager->flush();

        return ExternalSourceFactory::new()->with([
            'integration' => $integration,
            'kind' => ExternalSourceKind::DiscordForum,
            'externalId' => self::FORUM_CHANNEL_ID,
            'title' => 'character-threads',
            'config' => [
                'visibility' => $visibility,
                'defaultTargetType' => null,
                'forumTagMappings' => [
                    self::MAIN_PLOT_FORUM_TAG_ID => ['tagId' => $tag->getId()->toRfc4122(), 'targetType' => 'character'],
                ],
                'includePrivateThreads' => false,
                'guildId' => self::GUILD_ID,
                'parentId' => '1100000000000000100',
            ],
        ])->create()->_real();
    }

    private function syncSource(FunctionalTester $I, ExternalSource $source): void
    {
        $handler = $this->createSyncHandler($I);
        $transport = $this->integrationsTransport($I);
        $transport->reset();

        $handler(new SyncExternalSourceMessage($source->getId()));

        $handledMessages = 0;
        while (($nextEnvelope = $transport->getSent()[$handledMessages] ?? null) !== null) {
            $handledMessages++;
            $handler($nextEnvelope->getMessage());
        }
    }

    private function createSyncHandler(FunctionalTester $I): SyncExternalSourceHandler
    {
        return new SyncExternalSourceHandler(
            $I->grabService(ExternalSourceRepository::class),
            $I->grabService(ExternalItemRepository::class),
            $I->grabService(IntegrationRegistry::class),
            new SourceSyncIntervalPolicy(),
            $I->grabService(EntityManagerInterface::class),
            $I->grabService(LockFactory::class),
            $I->grabService(MessageBusInterface::class),
            new MockClock(self::NOW),
            new NullLogger(),
            $I->grabService(LinkSuggestionAggregator::class),
        );
    }

    private function referenceTo(ExternalItem $item): ExternalReference
    {
        $reference = new ExternalReference();
        $reference->setProvider(LarpIntegrationProvider::Discord);
        $reference->setExternalId($item->getExternalId());
        $reference->setReferenceType(ReferenceType::ForumPost);
        $reference->setRole(ReferenceRole::Primary);
        $reference->setLarp($item->getSource()->getIntegration()->getLarp());
        $reference->setExternalItem($item);

        return $reference;
    }

    private function discordRouter(FunctionalTester $I): FixtureRouter
    {
        return $I->grabService('tests.integrations.discord.fixture_router');
    }

    private function integrationsTransport(FunctionalTester $I): InMemoryTransport
    {
        return $I->grabService('messenger.transport.integrations');
    }

    /**
     * @return list<string>
     */
    private function requestSummaries(FunctionalTester $I, int $sinceRequest): array
    {
        return array_map(
            static fn (array $request): string => $request['method'] . ' ' . $request['path'],
            array_slice($this->discordRouter($I)->getRequests(), $sinceRequest),
        );
    }

    /**
     * @return list<array{method: string, path: string, query: array<string, mixed>}>
     */
    private function matchingRequests(FixtureRouter $router, int $sinceRequest, string $summary): array
    {
        return array_values(array_filter(
            array_slice($router->getRequests(), $sinceRequest),
            static fn (array $request): bool => $request['method'] . ' ' . $request['path'] === $summary,
        ));
    }

    private function reloadSource(FunctionalTester $I, ExternalSource $source): ExternalSource
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();

        return $entityManager->find(ExternalSource::class, $source->getId());
    }

    /**
     * @return array<string, ExternalItem>
     */
    private function itemsByExternalId(FunctionalTester $I, ExternalSource $source): array
    {
        $itemsByExternalId = [];
        foreach ($I->grabService(ExternalItemRepository::class)->findBy(['source' => $source]) as $item) {
            $itemsByExternalId[$item->getExternalId()] = $item;
        }
        ksort($itemsByExternalId, SORT_STRING);

        return $itemsByExternalId;
    }
}
