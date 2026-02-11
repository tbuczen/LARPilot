<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\VectorStore;

use App\Domain\StoryAI\DTO\VectorDocument;
use App\Domain\StoryAI\DTO\VectorSearchResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * No-op implementation of VectorStoreInterface.
 * Used when vector store is not configured or AI features are disabled.
 */
final readonly class NullVectorStore implements VectorStoreInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function upsert(VectorDocument $document): void
    {
        $this->logger?->debug('NullVectorStore: upsert ignored (vector store not configured)', [
            'entity_id' => $document->entityId->toRfc4122(),
        ]);
    }

    public function upsertBatch(array $documents): void
    {
        $this->logger?->debug('NullVectorStore: upsertBatch ignored (vector store not configured)', [
            'count' => count($documents),
        ]);
    }

    /**
     * @return VectorSearchResult[]
     */
    public function search(
        array $embedding,
        Uuid $larpId,
        int $limit = 10,
        float $minSimilarity = 0.5,
        array $filters = [],
    ): array {
        $this->logger?->debug('NullVectorStore: search returned empty (vector store not configured)');
        return [];
    }

    public function delete(Uuid $entityId): void
    {
        $this->logger?->debug('NullVectorStore: delete ignored (vector store not configured)', [
            'entity_id' => $entityId->toRfc4122(),
        ]);
    }

    public function deleteByFilter(array $filter): int
    {
        $this->logger?->debug('NullVectorStore: deleteByFilter ignored (vector store not configured)');
        return 0;
    }

    public function exists(Uuid $entityId): bool
    {
        return false;
    }

    public function findByEntityId(Uuid $entityId): ?VectorDocument
    {
        return null;
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function getProviderName(): string
    {
        return 'null';
    }
}
