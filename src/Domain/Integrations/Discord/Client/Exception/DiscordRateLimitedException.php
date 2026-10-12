<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client\Exception;

final class DiscordRateLimitedException extends DiscordApiException
{
    public static function retriesExhausted(string $path, int $attempts): self
    {
        return new self(sprintf('Discord kept rate limiting "%s" after %d attempts.', $path, $attempts), 429);
    }
}
