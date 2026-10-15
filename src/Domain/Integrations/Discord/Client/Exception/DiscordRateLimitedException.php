<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client\Exception;

final class DiscordRateLimitedException extends DiscordApiException
{
    private float $retryAfterSeconds = 0.0;

    public static function retriesExhausted(string $path, int $attempts, float $retryAfterSeconds = 0.0): self
    {
        $exception = new self(sprintf('Discord kept rate limiting "%s" after %d attempts.', $path, $attempts), 429);
        $exception->retryAfterSeconds = $retryAfterSeconds;

        return $exception;
    }

    public function getRetryAfterSeconds(): float
    {
        return $this->retryAfterSeconds;
    }
}
