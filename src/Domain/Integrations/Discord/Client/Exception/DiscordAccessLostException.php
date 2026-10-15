<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client\Exception;

final class DiscordAccessLostException extends DiscordApiException
{
    public static function forStatus(string $path, int $statusCode): self
    {
        return new self(
            sprintf('Discord denied access to "%s" (HTTP %d); the bot token is invalid or lacks permission.', $path, $statusCode),
            $statusCode,
        );
    }
}
