<?php

namespace App\Domain\Integrations\Discord\Picker;

use App\Domain\StoryObject\Entity\Enum\TargetType;

final readonly class DiscordForumTagMapping
{
    public function __construct(
        public ?string $tagId = null,
        public ?TargetType $targetType = null,
    ) {
    }

    /**
     * @param array<mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $tagId = $config['tagId'] ?? null;
        $targetType = $config['targetType'] ?? null;

        return new self(
            is_string($tagId) && $tagId !== '' ? $tagId : null,
            is_string($targetType) ? TargetType::tryFrom($targetType) : null,
        );
    }

    public function isEmpty(): bool
    {
        return $this->tagId === null && $this->targetType === null;
    }

    /**
     * @return array{tagId: ?string, targetType: ?string}
     */
    public function toArray(): array
    {
        return [
            'tagId' => $this->tagId,
            'targetType' => $this->targetType?->value,
        ];
    }
}
