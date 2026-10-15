<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client\Dto;

final readonly class DiscordForumTag
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $moderated,
        public ?string $emojiId,
        public ?string $emojiName,
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
            moderated: (bool) ($payload['moderated'] ?? false),
            emojiId: isset($payload['emoji_id']) ? (string) $payload['emoji_id'] : null,
            emojiName: isset($payload['emoji_name']) ? (string) $payload['emoji_name'] : null,
        );
    }
}
