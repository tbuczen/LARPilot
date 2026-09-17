<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\DTO;

/**
 * Represents a search result from vector similarity search.
 */
final readonly class SearchResult
{
    public const TYPE_STORY_OBJECT = 'story_object';
    public const TYPE_LORE_DOCUMENT = 'lore_document';

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $type,
        public string $id,
        public string $title,
        public string $content,
        public float $similarity,
        public ?string $entityId = null,
        public ?string $entityType = null,
        public array $metadata = [],
    ) {
    }

    public function isStoryObject(): bool
    {
        return $this->type === self::TYPE_STORY_OBJECT;
    }

    public function isLoreDocument(): bool
    {
        return $this->type === self::TYPE_LORE_DOCUMENT;
    }

    /**
     * Get a truncated version of the content for display.
     */
    public function getContentPreview(int $maxLength = 200): string
    {
        if (strlen($this->content) <= $maxLength) {
            return $this->content;
        }

        return substr($this->content, 0, $maxLength) . '...';
    }

    /**
     * Get similarity as a percentage.
     */
    public function getSimilarityPercent(): float
    {
        return round($this->similarity * 100, 1);
    }
}
