<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client\Dto;

final readonly class DiscordUser
{
    public function __construct(
        public string $id,
        public string $username,
        public ?string $globalName,
        public bool $bot,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: (string) $payload['id'],
            username: (string) ($payload['username'] ?? ''),
            globalName: isset($payload['global_name']) ? (string) $payload['global_name'] : null,
            bot: (bool) ($payload['bot'] ?? false),
        );
    }

    public function getDisplayName(): string
    {
        return $this->globalName ?? $this->username;
    }
}
