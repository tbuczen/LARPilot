<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client\Dto;

use DateTimeImmutable;

final readonly class DiscordThread
{
    /**
     * @param list<string> $appliedTags
     */
    public function __construct(
        public string $id,
        public int $type,
        public ?string $guildId,
        public ?string $parentId,
        public ?string $name,
        public array $appliedTags,
        public bool $archived,
        public bool $locked,
        public ?DateTimeImmutable $archiveTimestamp,
        public ?string $lastMessageId,
        public ?int $messageCount,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $threadMetadata = $payload['thread_metadata'] ?? [];
        $appliedTags = array_values(array_map(strval(...), $payload['applied_tags'] ?? []));

        return new self(
            id: (string) $payload['id'],
            type: (int) $payload['type'],
            guildId: isset($payload['guild_id']) ? (string) $payload['guild_id'] : null,
            parentId: isset($payload['parent_id']) ? (string) $payload['parent_id'] : null,
            name: isset($payload['name']) ? (string) $payload['name'] : null,
            appliedTags: $appliedTags,
            archived: (bool) ($threadMetadata['archived'] ?? false),
            locked: (bool) ($threadMetadata['locked'] ?? false),
            archiveTimestamp: isset($threadMetadata['archive_timestamp']) ? new DateTimeImmutable((string) $threadMetadata['archive_timestamp']) : null,
            lastMessageId: isset($payload['last_message_id']) ? (string) $payload['last_message_id'] : null,
            messageCount: isset($payload['message_count']) ? (int) $payload['message_count'] : null,
        );
    }
}
