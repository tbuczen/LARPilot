<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\VectorStore;

use App\Domain\StoryAI\DTO\VectorDocument;
use App\Domain\StoryAI\DTO\VectorSearchResult;
use Symfony\Component\Uid\Uuid;

/**
 * Interface for vector store operations.
 * Abstracts the underlying vector database (Supabase, Neon, local pgvector, etc.)
 * to enable CQRS separation between write (main DB) and read (vector DB) sides.
 */
interface VectorStoreInterface
{
    /**
     * Insert or update a document with its embedding.
     * Uses entity_id as the unique identifier for upsert logic.
     */
    public function upsert(VectorDocument $document): void;

    /**
     * Batch upsert multiple documents.
     *
     * @param VectorDocument[] $documents
     */
    public function upsertBatch(array $documents): void;

    /**
     * Search for similar documents using vector similarity.
     *
     * @param array<int, float> $embedding Query embedding vector
     * @param Uuid $larpId Filter by LARP
     * @param int $limit Maximum results to return
     * @param float $minSimilarity Minimum cosine similarity threshold (0-1)
     * @param array<string, mixed> $filters Additional metadata filters
     * @return VectorSearchResult[]
     */
    public function search(
        array $embedding,
        Uuid $larpId,
        int $limit = 10,
        float $minSimilarity = 0.5,
        array $filters = [],
    ): array;

    /**
     * Delete a document by its entity ID.
     */
    public function delete(Uuid $entityId): void;

    /**
     * Delete all documents matching a filter.
     *
     * @param array<string, mixed> $filter Filter criteria (e.g., ['larp_id' => $uuid])
     */
    public function deleteByFilter(array $filter): int;

    /**
     * Check if a document exists for the given entity.
     */
    public function exists(Uuid $entityId): bool;

    /**
     * Get document by entity ID (for cache checking).
     */
    public function findByEntityId(Uuid $entityId): ?VectorDocument;

    /**
     * Check if the vector store is available/configured.
     */
    public function isAvailable(): bool;

    /**
     * Get the name of the vector store provider.
     */
    public function getProviderName(): string;
}
