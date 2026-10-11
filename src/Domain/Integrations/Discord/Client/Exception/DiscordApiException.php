<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client\Exception;

use RuntimeException;

class DiscordApiException extends RuntimeException
{
    public static function unexpectedStatus(string $path, int $statusCode): self
    {
        return new self(sprintf('Discord API request to "%s" failed with HTTP %d.', $path, $statusCode), $statusCode);
    }

    public static function invalidPayload(string $path): self
    {
        return new self(sprintf('Discord API request to "%s" returned an unexpected payload.', $path));
    }
}
