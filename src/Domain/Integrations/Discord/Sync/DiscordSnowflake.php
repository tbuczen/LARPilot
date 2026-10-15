<?php

namespace App\Domain\Integrations\Discord\Sync;

final class DiscordSnowflake
{
    public const DISCORD_EPOCH_MILLISECONDS = 1420070400000;

    public static function createdAt(string $snowflake): ?\DateTimeImmutable
    {
        if (!ctype_digit($snowflake) || strlen($snowflake) > 19) {
            return null;
        }

        $createdAtMilliseconds = ((int) $snowflake >> 22) + self::DISCORD_EPOCH_MILLISECONDS;
        $createdAt = \DateTimeImmutable::createFromFormat(
            'U.v',
            sprintf('%d.%03d', intdiv($createdAtMilliseconds, 1000), $createdAtMilliseconds % 1000),
            new \DateTimeZone('UTC'),
        );

        return $createdAt === false ? null : $createdAt;
    }

    public static function compare(string $left, string $right): int
    {
        return [strlen($left), $left] <=> [strlen($right), $right];
    }
}
