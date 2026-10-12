<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client\Dto;

final readonly class DiscordChannel
{
    /**
     * @param list<DiscordForumTag> $availableTags
     */
    public function __construct(
        public string $id,
        public int $type,
        public ?string $guildId,
        public ?string $name,
        public ?string $parentId,
        public ?int $position,
        public array $availableTags,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $availableTags = [];
        foreach ($payload['available_tags'] ?? [] as $tagPayload) {
            $availableTags[] = DiscordForumTag::fromArray($tagPayload);
        }

        return new self(
            id: (string) $payload['id'],
            type: (int) $payload['type'],
            guildId: isset($payload['guild_id']) ? (string) $payload['guild_id'] : null,
            name: isset($payload['name']) ? (string) $payload['name'] : null,
            parentId: isset($payload['parent_id']) ? (string) $payload['parent_id'] : null,
            position: isset($payload['position']) ? (int) $payload['position'] : null,
            availableTags: $availableTags,
        );
    }

    public function getChannelType(): ?DiscordChannelType
    {
        return DiscordChannelType::tryFrom($this->type);
    }

    public function isForum(): bool
    {
        return in_array($this->getChannelType(), [DiscordChannelType::GUILD_FORUM, DiscordChannelType::GUILD_MEDIA], true);
    }
}
