<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client\Dto;

final readonly class DiscordBotAuthorization
{
    public function __construct(
        public string $accessToken,
        public ?string $guildId,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $guild = $payload['guild'] ?? null;
        $guildId = is_array($guild) && isset($guild['id']) ? (string) $guild['id'] : null;

        return new self(
            accessToken: (string) ($payload['access_token'] ?? ''),
            guildId: $guildId,
        );
    }
}
