<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\Query;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\DTO\SearchResult;
use App\Domain\StoryAI\DTO\VectorDocument;
use App\Domain\StoryAI\DTO\VectorSearchResult;
use App\Domain\StoryAI\Service\Embedding\EmbeddingService;
use App\Domain\StoryAI\Service\VectorStore\VectorStoreInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for performing vector similarity searches across indexed content.
 *
 * This service follows CQRS principles by reading from the external vector store.
 */
readonly class VectorSearchService
{
    public function __construct(
        private EmbeddingService $embeddingService,
        private VectorStoreInterface $vectorStore,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Search across all indexed content (story objects + lore documents).
     *
     * @return SearchResult[]
     */
    public function search(
        string $query,
        Larp $larp,
        int $limit = 10,
        float $minSimilarity = 0.5,
    ): array {
        $queryEmbedding = $this->embeddingService->generateQueryEmbedding($query);

        $this->logger?->debug('Performing vector search', [
            'query' => $query,
            'larp_id' => $larp->getId()->toRfc4122(),
            'limit' => $limit,
            'vector_store' => $this->vectorStore->getProviderName(),
        ]);

        $results = $this->vectorStore->search(
            embedding: $queryEmbedding,
            larpId: $larp->getId(),
            limit: $limit,
            minSimilarity: $minSimilarity,
        );

        return array_map(
            fn (VectorSearchResult $result) => $result->toSearchResult(),
            $results
        );
    }

    /**
     * Search only story objects.
     *
     * @return SearchResult[]
     */
    public function searchStoryObjects(
        string $query,
        Larp $larp,
        int $limit = 10,
        float $minSimilarity = 0.5,
    ): array {
        $queryEmbedding = $this->embeddingService->generateQueryEmbedding($query);

        $results = $this->vectorStore->search(
            embedding: $queryEmbedding,
            larpId: $larp->getId(),
            limit: $limit,
            minSimilarity: $minSimilarity,
            filters: ['type' => VectorDocument::TYPE_STORY_OBJECT],
        );

        return array_map(
            fn (VectorSearchResult $result) => $result->toSearchResult(),
            $results
        );
    }

    /**
     * Search only lore documents.
     *
     * @return SearchResult[]
     */
    public function searchLoreDocuments(
        string $query,
        Larp $larp,
        int $limit = 10,
        float $minSimilarity = 0.5,
    ): array {
        $queryEmbedding = $this->embeddingService->generateQueryEmbedding($query);

        $results = $this->vectorStore->search(
            embedding: $queryEmbedding,
            larpId: $larp->getId(),
            limit: $limit,
            minSimilarity: $minSimilarity,
            filters: ['type' => VectorDocument::TYPE_LORE_CHUNK],
        );

        return array_map(
            fn (VectorSearchResult $result) => $result->toSearchResult(),
            $results
        );
    }

    /**
     * Search with custom options.
     *
     * @return SearchResult[]
     */
    public function searchByQuery(
        string $query,
        Larp $larp,
        int $limit = 10,
        float $minSimilarity = 0.5,
        bool $includeStoryObjects = true,
        bool $includeLoreDocuments = true,
        ?string $entityType = null,
    ): array {
        $queryEmbedding = $this->embeddingService->generateQueryEmbedding($query);

        $filters = [];

        // Filter by document type
        if ($includeStoryObjects && !$includeLoreDocuments) {
            $filters['type'] = VectorDocument::TYPE_STORY_OBJECT;
        } elseif (!$includeStoryObjects && $includeLoreDocuments) {
            $filters['type'] = VectorDocument::TYPE_LORE_CHUNK;
        }

        // Filter by entity type (Character, Thread, Quest, etc.)
        if ($entityType) {
            $filters['entity_type'] = $entityType;
        }

        $results = $this->vectorStore->search(
            embedding: $queryEmbedding,
            larpId: $larp->getId(),
            limit: $limit,
            minSimilarity: $minSimilarity,
            filters: $filters,
        );

        return array_map(
            fn (VectorSearchResult $result) => $result->toSearchResult(),
            $results
        );
    }

    /**
     * Search with a pre-computed embedding vector.
     *
     * @param array<int, float> $embedding
     * @return SearchResult[]
     */
    public function searchByEmbedding(
        array $embedding,
        Larp $larp,
        int $limit = 10,
        float $minSimilarity = 0.5,
        array $filters = [],
    ): array {
        $results = $this->vectorStore->search(
            embedding: $embedding,
            larpId: $larp->getId(),
            limit: $limit,
            minSimilarity: $minSimilarity,
            filters: $filters,
        );

        return array_map(
            fn (VectorSearchResult $result) => $result->toSearchResult(),
            $results
        );
    }

    /**
     * Check if the vector store is available for searches.
     */
    public function isAvailable(): bool
    {
        return $this->vectorStore->isAvailable();
    }

    /**
     * Get the vector store provider name.
     */
    public function getProviderName(): string
    {
        return $this->vectorStore->getProviderName();
    }
}
