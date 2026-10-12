<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client\Dto;

final readonly class DiscordGuild
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $icon,
        public ?string $ownerId,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: (string) $payload['id'],
            name: (string) ($payload['name'] ?? ''),
            icon: isset($payload['icon']) ? (string) $payload['icon'] : null,
            ownerId: isset($payload['owner_id']) ? (string) $payload['owner_id'] : null,
        );
    }
}
