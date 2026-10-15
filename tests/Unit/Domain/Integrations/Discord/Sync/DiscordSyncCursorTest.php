<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Discord\Sync;

use App\Domain\Integrations\Discord\Sync\DiscordSnowflake;
use App\Domain\Integrations\Discord\Sync\DiscordSyncCursor;
use PHPUnit\Framework\TestCase;

class DiscordSyncCursorTest extends TestCase
{
    public function testBackfillKeepsTheStopPointUntilItCompletes(): void
    {
        $cursor = DiscordSyncCursor::fromArray(['archivedThrough' => '2026-08-01T00:00:00.000000+00:00']);

        $backfilling = $cursor->continueBackfill(new \DateTimeImmutable('2026-08-10T00:00:00+00:00'), new \DateTimeImmutable('2026-09-01T00:00:00+00:00'));
        self::assertTrue($backfilling->isBackfilling());
        self::assertEquals(new \DateTimeImmutable('2026-08-01T00:00:00+00:00'), $backfilling->archivedThrough);

        $completed = DiscordSyncCursor::fromArray($backfilling->toArray())->completeBackfill(new \DateTimeImmutable('2026-08-12T00:00:00+00:00'));
        self::assertFalse($completed->isBackfilling());
        self::assertSame(
            ['archivedThrough' => '2026-09-01T00:00:00.000000+00:00', 'backfillBefore' => null, 'backfillNewest' => null],
            $completed->toArray(),
        );
    }

    public function testInvalidCursorStartsFromScratch(): void
    {
        self::assertSame(
            ['archivedThrough' => null, 'backfillBefore' => null, 'backfillNewest' => null],
            DiscordSyncCursor::fromArray(['archivedThrough' => 'not a date', 'backfillBefore' => 42])->toArray(),
        );
    }

    public function testSnowflakeEncodesItsCreationTime(): void
    {
        self::assertEquals(new \DateTimeImmutable('2016-04-30T11:18:25.796+00:00'), DiscordSnowflake::createdAt('175928847299117063'));
        self::assertNull(DiscordSnowflake::createdAt('not-a-snowflake'));
        self::assertSame(1, DiscordSnowflake::compare('1000', '999'));
    }
}
