<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\DTO;

use Symfony\Component\Uid\Uuid;

/**
 * Raw result from vector similarity search.
 * This is the direct response from the vector store, which can be
 * transformed into the richer SearchResult DTO by the service layer.
 */
final readonly class VectorSearchResult
{
    /**
     * @param Uuid $entityId The ID of the matched entity
     * @param Uuid $larpId The LARP ID
     * @param string $entityType The type of entity
     * @param string $type Document type (story_object or lore_chunk)
     * @param string $title Document title
     * @param string $content The serialized content
     * @param float $similarity Cosine similarity score (0-1)
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public Uuid $entityId,
        public Uuid $larpId,
        public string $entityType,
        public string $type,
        public string $title,
        public string $content,
        public float $similarity,
        public array $metadata = [],
    ) {
    }

    /**
     * Create from array (for hydration from API response).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entityId: Uuid::fromString($data['entity_id']),
            larpId: Uuid::fromString($data['larp_id']),
            entityType: $data['entity_type'],
            type: $data['type'],
            title: $data['title'],
            content: $data['serialized_content'] ?? $data['content'] ?? '',
            similarity: (float) $data['similarity'],
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Convert to the richer SearchResult DTO.
     */
    public function toSearchResult(): SearchResult
    {
        return new SearchResult(
            type: $this->type,
            id: $this->entityId->toRfc4122(),
            title: $this->title,
            content: $this->content,
            similarity: $this->similarity,
            entityId: $this->entityId->toRfc4122(),
            entityType: $this->entityType,
            metadata: $this->metadata,
        );
    }

    public function isStoryObject(): bool
    {
        return $this->type === VectorDocument::TYPE_STORY_OBJECT;
    }

    public function isLoreChunk(): bool
    {
        return $this->type === VectorDocument::TYPE_LORE_CHUNK;
    }

    /**
     * Get similarity as percentage.
     */
    public function getSimilarityPercent(): float
    {
        return round($this->similarity * 100, 1);
    }
}
