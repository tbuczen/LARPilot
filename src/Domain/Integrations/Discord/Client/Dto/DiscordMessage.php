<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client\Dto;

use DateTimeImmutable;

final readonly class DiscordMessage
{
    /**
     * @param array<string, string> $mentionedUserNames
     */
    public function __construct(
        public string $id,
        public string $channelId,
        public DiscordUser $author,
        public string $content,
        public DateTimeImmutable $timestamp,
        public ?DateTimeImmutable $editedTimestamp,
        public array $mentionedUserNames = [],
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $mentionedUserNames = [];
        foreach ($payload['mentions'] ?? [] as $mentionPayload) {
            if (!is_array($mentionPayload) || !isset($mentionPayload['id'])) {
                continue;
            }

            $mentionedUserNames[(string) $mentionPayload['id']] = DiscordUser::fromArray($mentionPayload)->getDisplayName();
        }

        return new self(
            id: (string) $payload['id'],
            channelId: (string) $payload['channel_id'],
            author: DiscordUser::fromArray($payload['author']),
            content: (string) ($payload['content'] ?? ''),
            timestamp: new DateTimeImmutable((string) $payload['timestamp']),
            editedTimestamp: isset($payload['edited_timestamp']) ? new DateTimeImmutable((string) $payload['edited_timestamp']) : null,
            mentionedUserNames: $mentionedUserNames,
        );
    }
}
