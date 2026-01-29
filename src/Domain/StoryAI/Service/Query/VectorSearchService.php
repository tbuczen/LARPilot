<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\Query;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\DTO\SearchResult;
use App\Domain\StoryAI\Entity\LoreDocumentChunk;
use App\Domain\StoryAI\Entity\StoryObjectEmbedding;
use App\Domain\StoryAI\Repository\LoreDocumentChunkRepository;
use App\Domain\StoryAI\Repository\StoryObjectEmbeddingRepository;
use App\Domain\StoryAI\Service\Embedding\EmbeddingService;
use Psr\Log\LoggerInterface;

/**
 * Service for performing vector similarity searches across indexed content.
 */
readonly class VectorSearchService
{
    public function __construct(
        private EmbeddingService               $embeddingService,
        private StoryObjectEmbeddingRepository $storyObjectEmbeddingRepository,
        private LoreDocumentChunkRepository    $loreDocumentChunkRepository,
        private ?LoggerInterface               $logger = null,
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
        ]);

        // Search story objects
        $storyResults = $this->searchStoryObjects($queryEmbedding, $larp, $limit, $minSimilarity);

        // Search lore documents
        $loreResults = $this->searchLoreDocuments($queryEmbedding, $larp, $limit, $minSimilarity);

        // Merge and sort by similarity
        $allResults = array_merge($storyResults, $loreResults);
        usort($allResults, fn (SearchResult $a, SearchResult $b) => $b->similarity <=> $a->similarity);

        // Return top results up to the limit
        return array_slice($allResults, 0, $limit);
    }

    /**
     * Search only story objects.
     *
     * @return SearchResult[]
     */
    public function searchStoryObjects(
        array $queryEmbedding,
        Larp $larp,
        int $limit = 10,
        float $minSimilarity = 0.5,
    ): array {
        $results = $this->storyObjectEmbeddingRepository->findSimilar(
            $queryEmbedding,
            $larp,
            $limit,
            $minSimilarity
        );

        return array_map(
            fn (array $row) => $this->createStoryObjectResult($row['embedding'], $row['similarity']),
            $results
        );
    }

    /**
     * Search only lore documents.
     *
     * @return SearchResult[]
     */
    public function searchLoreDocuments(
        array $queryEmbedding,
        Larp $larp,
        int $limit = 10,
        float $minSimilarity = 0.5,
    ): array {
        $results = $this->loreDocumentChunkRepository->findSimilar(
            $queryEmbedding,
            $larp,
            $limit,
            $minSimilarity
        );

        return array_map(
            fn (array $row) => $this->createLoreChunkResult($row['chunk'], $row['similarity']),
            $results
        );
    }

    /**
     * Search by query string (generates embedding internally).
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
    ): array {
        $queryEmbedding = $this->embeddingService->generateQueryEmbedding($query);

        $results = [];

        if ($includeStoryObjects) {
            $results = array_merge(
                $results,
                $this->searchStoryObjects($queryEmbedding, $larp, $limit, $minSimilarity)
            );
        }

        if ($includeLoreDocuments) {
            $results = array_merge(
                $results,
                $this->searchLoreDocuments($queryEmbedding, $larp, $limit, $minSimilarity)
            );
        }

        // Sort by similarity
        usort($results, fn (SearchResult $a, SearchResult $b) => $b->similarity <=> $a->similarity);

        return array_slice($results, 0, $limit);
    }

    private function createStoryObjectResult(StoryObjectEmbedding $embedding, float $similarity): SearchResult
    {
        $storyObject = $embedding->getStoryObject();

        return new SearchResult(
            type: SearchResult::TYPE_STORY_OBJECT,
            id: $embedding->getId()->toRfc4122(),
            title: $storyObject?->getTitle() ?? 'Unknown',
            content: $embedding->getSerializedContent(),
            similarity: $similarity,
            entityId: $storyObject?->getId()->toRfc4122(),
            entityType: $storyObject ? (new \ReflectionClass($storyObject))->getShortName() : null,
        );
    }

    private function createLoreChunkResult(LoreDocumentChunk $chunk, float $similarity): SearchResult
    {
        $document = $chunk->getDocument();

        return new SearchResult(
            type: SearchResult::TYPE_LORE_DOCUMENT,
            id: $chunk->getId()->toRfc4122(),
            title: $document?->getTitle() ?? 'Unknown Document',
            content: $chunk->getContent(),
            similarity: $similarity,
            entityId: $document?->getId()->toRfc4122(),
            entityType: $document?->getType()->getLabel(),
            metadata: [
                'chunk_index' => $chunk->getChunkIndex(),
                'document_type' => $document?->getType()->value,
            ],
        );
    }
}
