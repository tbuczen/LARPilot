<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client\Dto;

use DateTimeImmutable;

final readonly class DiscordMessage
{
    public function __construct(
        public string $id,
        public string $channelId,
        public DiscordUser $author,
        public string $content,
        public DateTimeImmutable $timestamp,
        public ?DateTimeImmutable $editedTimestamp,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: (string) $payload['id'],
            channelId: (string) $payload['channel_id'],
            author: DiscordUser::fromArray($payload['author']),
            content: (string) ($payload['content'] ?? ''),
            timestamp: new DateTimeImmutable((string) $payload['timestamp']),
            editedTimestamp: isset($payload['edited_timestamp']) ? new DateTimeImmutable((string) $payload['edited_timestamp']) : null,
        );
    }
}
