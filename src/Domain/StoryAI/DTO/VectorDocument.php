<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\DTO;

use Symfony\Component\Uid\Uuid;

/**
 * Represents a document to be stored in the vector database.
 * Used for both storage (upsert) and retrieval operations.
 */
final readonly class VectorDocument
{
    public const TYPE_STORY_OBJECT = 'story_object';
    public const TYPE_LORE_CHUNK = 'lore_chunk';

    /**
     * @param Uuid $entityId The ID of the source entity (StoryObject UUID or generated UUID for lore chunks)
     * @param Uuid $larpId The LARP this document belongs to
     * @param string $entityType The type of entity (Character, Thread, Quest, etc.)
     * @param string $type Document type (story_object or lore_chunk)
     * @param string $title Title for display purposes
     * @param string $serializedContent The text content that was embedded
     * @param string $contentHash SHA-256 hash for change detection
     * @param array<int, float> $embedding The vector embedding
     * @param string $embeddingModel The model used to generate the embedding
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public Uuid $entityId,
        public Uuid $larpId,
        public string $entityType,
        public string $type,
        public string $title,
        public string $serializedContent,
        public string $contentHash,
        public array $embedding,
        public string $embeddingModel = 'text-embedding-3-small',
        public array $metadata = [],
    ) {
    }

    /**
     * Create from a story object embedding context.
     *
     * @param array<int, float> $embedding
     * @param array<string, mixed> $metadata
     */
    public static function forStoryObject(
        Uuid $entityId,
        Uuid $larpId,
        string $entityType,
        string $title,
        string $serializedContent,
        array $embedding,
        string $embeddingModel = 'text-embedding-3-small',
        array $metadata = [],
    ): self {
        return new self(
            entityId: $entityId,
            larpId: $larpId,
            entityType: $entityType,
            type: self::TYPE_STORY_OBJECT,
            title: $title,
            serializedContent: $serializedContent,
            contentHash: hash('sha256', $serializedContent),
            embedding: $embedding,
            embeddingModel: $embeddingModel,
            metadata: $metadata,
        );
    }

    /**
     * Create from a lore document chunk context.
     *
     * @param array<int, float> $embedding
     * @param array<string, mixed> $metadata
     */
    public static function forLoreChunk(
        Uuid $entityId,
        Uuid $larpId,
        string $documentTitle,
        string $chunkContent,
        int $chunkIndex,
        array $embedding,
        string $embeddingModel = 'text-embedding-3-small',
        array $metadata = [],
    ): self {
        return new self(
            entityId: $entityId,
            larpId: $larpId,
            entityType: 'LoreDocumentChunk',
            type: self::TYPE_LORE_CHUNK,
            title: sprintf('%s (chunk %d)', $documentTitle, $chunkIndex + 1),
            serializedContent: $chunkContent,
            contentHash: hash('sha256', $chunkContent),
            embedding: $embedding,
            embeddingModel: $embeddingModel,
            metadata: array_merge($metadata, ['chunk_index' => $chunkIndex]),
        );
    }

    /**
     * Convert to array for API transmission.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'entity_id' => $this->entityId->toRfc4122(),
            'larp_id' => $this->larpId->toRfc4122(),
            'entity_type' => $this->entityType,
            'type' => $this->type,
            'title' => $this->title,
            'serialized_content' => $this->serializedContent,
            'content_hash' => $this->contentHash,
            'embedding' => $this->embedding,
            'embedding_model' => $this->embeddingModel,
            'metadata' => $this->metadata,
        ];
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
            serializedContent: $data['serialized_content'],
            contentHash: $data['content_hash'],
            embedding: $data['embedding'],
            embeddingModel: $data['embedding_model'] ?? 'text-embedding-3-small',
            metadata: $data['metadata'] ?? [],
        );
    }

    public function isStoryObject(): bool
    {
        return $this->type === self::TYPE_STORY_OBJECT;
    }

    public function isLoreChunk(): bool
    {
        return $this->type === self::TYPE_LORE_CHUNK;
    }
}
