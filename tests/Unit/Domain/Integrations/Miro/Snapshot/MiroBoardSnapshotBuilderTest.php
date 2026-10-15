<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Miro\Snapshot;

use App\Domain\Integrations\Entity\Enum\ExternalItemAvailability;
use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Miro\Client\MiroApiClient;
use App\Domain\Integrations\Miro\Client\MiroRateLimitExceededException;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardSnapshot;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardSnapshotBuilder;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardUrls;
use Codeception\Test\Unit;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Tests\Support\Http\FixtureRouter;
use Tests\Support\Http\IntegrationFixtureRouters;

class MiroBoardSnapshotBuilderTest extends Unit
{
    private const BOARD_ID = IntegrationFixtureRouters::MIRO_BOARD_ID;
    private const FRAME_ID = IntegrationFixtureRouters::MIRO_FRAME_ID;
    private const STICKY_NOTE_ID = '3458764600000000010';
    private const CARD_ID = '3458764600000000012';
    private const LADY_MORWEN_ID = '3458764600000000101';

    public function testBuildsFramesItemsAndConnectorsFromAllPages(): void
    {
        $snapshot = $this->buildSnapshot(IntegrationFixtureRouters::miro());

        $this->assertSame(self::BOARD_ID, $snapshot->boardId);
        $this->assertEquals(new \DateTimeImmutable('2026-10-13 09:00:00'), $snapshot->builtAt);
        $this->assertSame([
            self::FRAME_ID,
            self::STICKY_NOTE_ID,
            '3458764600000000011',
            self::CARD_ID,
            '3458764600000000013',
            self::LADY_MORWEN_ID,
            '3458764600000000102',
            '3458764600000000501',
            '3458764600000000502',
            '3458764600000000503',
        ], $snapshot->externalIds());

        $frames = $snapshot->frames();
        $this->assertCount(1, $frames);
        $this->assertSame('House Morwen', $frames[0]->title);
        $this->assertSame(ExternalItemKind::Frame, $frames[0]->kind);
        $this->assertNull($frames[0]->parentExternalId);

        $this->assertSame(['Lady Morwen', 'Cousin Elric'], $this->titles($snapshot->itemsInFrame(self::FRAME_ID)));
        $this->assertSame(
            ['The stolen seal is hidden in the chapel', "Smugglers' Guild", 'Council convenes', 'Act II'],
            $this->titles($snapshot->itemsOutsideFrames()),
        );
    }

    public function testNormalizesItemsToPlainTextWithTags(): void
    {
        $snapshot = $this->buildSnapshot(IntegrationFixtureRouters::miro());

        $stickyNote = $snapshot->entry(self::STICKY_NOTE_ID);
        $this->assertInstanceOf(MiroBoardEntry::class, $stickyNote);
        $this->assertSame(ExternalItemKind::BoardItem, $stickyNote->kind);
        $this->assertSame('sticky_note', $stickyNote->type);
        $this->assertSame('The stolen seal is hidden in the chapel', $stickyNote->text);
        $this->assertSame(['Main plot'], $stickyNote->tags);
        $this->assertSame("The stolen seal is hidden in the chapel\n#Main plot", $stickyNote->plainText());
        $this->assertEquals(new \DateTimeImmutable('2026-09-01T09:00:00Z'), $stickyNote->updatedAt);

        $card = $snapshot->entry(self::CARD_ID);
        $this->assertInstanceOf(MiroBoardEntry::class, $card);
        $this->assertSame('Council convenes', $card->title);
        $this->assertSame('Saturday evening, great hall', $card->text);
        $this->assertSame(['Timeline'], $card->tags);

        $ladyMorwen = $snapshot->entry(self::LADY_MORWEN_ID);
        $this->assertInstanceOf(MiroBoardEntry::class, $ladyMorwen);
        $this->assertSame(self::FRAME_ID, $ladyMorwen->parentExternalId);
        $this->assertSame(
            'https://miro.com/app/board/uXjVOD6LSME=/?moveToWidget=' . self::LADY_MORWEN_ID,
            $ladyMorwen->url,
        );
    }

    public function testDescribesConnectorsAsStartCaptionAndEnd(): void
    {
        $snapshot = $this->buildSnapshot(IntegrationFixtureRouters::miro());

        $this->assertSame([
            'Lady Morwen — suspects → Cousin Elric',
            "Lady Morwen — owes a favour secret → Smugglers' Guild",
            'The stolen seal is hidden in the chapel → Council convenes',
        ], $this->titles($snapshot->connectors()));

        $connector = $snapshot->connectors()[0];
        $this->assertSame(ExternalItemKind::Connector, $connector->kind);
        $this->assertSame('suspects', $connector->text);
        $this->assertSame(self::LADY_MORWEN_ID, $connector->startItemId);
    }

    public function testConvertsEntriesToSyncedItemsWithAvailability(): void
    {
        $snapshot = $this->buildSnapshot(IntegrationFixtureRouters::miro());
        $ladyMorwen = $snapshot->entry(self::LADY_MORWEN_ID);
        $this->assertInstanceOf(MiroBoardEntry::class, $ladyMorwen);

        $syncedItem = $ladyMorwen->toSyncedExternalItem(ExternalItemAvailability::Changed);

        $this->assertSame(self::LADY_MORWEN_ID, $syncedItem->externalId);
        $this->assertSame(ExternalItemKind::BoardItem, $syncedItem->kind);
        $this->assertSame('Lady Morwen', $syncedItem->title);
        $this->assertSame(self::FRAME_ID, $syncedItem->parentExternalId);
        $this->assertSame(['type' => 'sticky_note'], $syncedItem->raw);
        $this->assertSame(ExternalItemAvailability::Changed, $syncedItem->availability);
    }

    public function testRateLimitWithoutRetriesExposesRetryAfter(): void
    {
        $router = IntegrationFixtureRouters::miro()->on(
            'GET',
            '/boards/{board_id}/tags',
            'rate_limited.json',
            429,
            ['x-ratelimit-reset' => '30'],
        );
        $sleptSeconds = [];
        $miroApiClient = new MiroApiClient(
            new MockHttpClient($router, IntegrationFixtureRouters::MIRO_BASE_URI),
            'test-token',
            static function (int $seconds) use (&$sleptSeconds): void {
                $sleptSeconds[] = $seconds;
            },
            maxRateLimitRetries: 0,
        );

        try {
            (new MiroBoardSnapshotBuilder(new MockClock()))->build($miroApiClient, self::BOARD_ID);
            $this->fail('Expected the rate limit to be reported');
        } catch (MiroRateLimitExceededException $rateLimited) {
            $this->assertSame(30, $rateLimited->getRetryAfterSeconds());
            $this->assertSame(429, $rateLimited->getHttpStatusCode());
        }

        $this->assertSame([], $sleptSeconds);
    }

    public function testBuildsMiroUrls(): void
    {
        $this->assertSame('https://miro.com/app/board/uXjVOD6LSME=/', MiroBoardUrls::board(self::BOARD_ID));
        $this->assertSame('https://miro.com/app/board/uXjVOD6LSME=/?moveToWidget=42', MiroBoardUrls::item(self::BOARD_ID, '42'));
        $this->assertSame('https://miro.com/app/live-embed/uXjVOD6LSME=/?autoplay=true', MiroBoardUrls::liveEmbed(self::BOARD_ID));
    }

    private function buildSnapshot(FixtureRouter $router): MiroBoardSnapshot
    {
        $miroApiClient = new MiroApiClient(new MockHttpClient($router, IntegrationFixtureRouters::MIRO_BASE_URI), 'test-token');

        return (new MiroBoardSnapshotBuilder(new MockClock('2026-10-13 09:00:00')))->build($miroApiClient, self::BOARD_ID);
    }

    /**
     * @param list<MiroBoardEntry> $entries
     * @return list<string>
     */
    private function titles(array $entries): array
    {
        return array_map(static fn (MiroBoardEntry $entry): string => $entry->title, $entries);
    }
}
