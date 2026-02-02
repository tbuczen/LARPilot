<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\Embedding;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\DTO\VectorDocument;
use App\Domain\StoryAI\Service\Provider\EmbeddingProviderInterface;
use App\Domain\StoryAI\Service\VectorStore\VectorStoreInterface;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for generating and managing embeddings.
 *
 * All embedding data is stored in the external vector store (Supabase).
 * No local database entities are used for AI/embedding tracking.
 */
class EmbeddingService
{
    public function __construct(
        private readonly EmbeddingProviderInterface $embeddingProvider,
        private readonly StoryObjectSerializer $serializer,
        private readonly VectorStoreInterface $vectorStore,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Index a story object by generating and storing its embedding in the vector store.
     */
    public function indexStoryObject(StoryObject $storyObject, bool $force = false): void
    {
        $larp = $storyObject->getLarp();
        if (!$larp) {
            throw new \InvalidArgumentException('Story object must belong to a LARP');
        }

        // Serialize the story object to text
        $serializedContent = $this->serializer->serialize($storyObject);
        $contentHash = hash('sha256', $serializedContent);

        // Check if content has changed (unless forced)
        if (!$force) {
            $existing = $this->vectorStore->findByEntityId($storyObject->getId());
            if ($existing && $existing->contentHash === $contentHash) {
                $this->logger?->debug('Content unchanged, skipping re-embedding', [
                    'story_object_id' => $storyObject->getId()->toRfc4122(),
                ]);
                return;
            }
        }

        // Generate embedding vector
        $vector = $this->embeddingProvider->embed($serializedContent);

        // Create VectorDocument and upsert to external store
        $entityType = (new \ReflectionClass($storyObject))->getShortName();
        $vectorDocument = VectorDocument::forStoryObject(
            entityId: $storyObject->getId(),
            larpId: $larp->getId(),
            entityType: $entityType,
            title: $storyObject->getTitle(),
            serializedContent: $serializedContent,
            embedding: $vector,
            embeddingModel: $this->embeddingProvider->getModelName(),
        );

        $this->vectorStore->upsert($vectorDocument);

        $this->logger?->info('Story object indexed successfully', [
            'story_object_id' => $storyObject->getId()->toRfc4122(),
            'type' => $entityType,
            'vector_store' => $this->vectorStore->getProviderName(),
        ]);
    }

    /**
     * Reindex all story objects for a LARP.
     *
     * @return array{indexed: int, skipped: int, errors: int}
     */
    public function reindexLarp(Larp $larp, ?callable $progressCallback = null, bool $force = false): array
    {
        $stats = ['indexed' => 0, 'skipped' => 0, 'errors' => 0];

        // Get all story objects for this LARP
        $storyObjects = $this->entityManager
            ->getRepository(StoryObject::class)
            ->findBy(['larp' => $larp]);

        $total = count($storyObjects);
        $this->logger?->info('Starting LARP reindex', [
            'larp_id' => $larp->getId()->toRfc4122(),
            'total_objects' => $total,
            'vector_store' => $this->vectorStore->getProviderName(),
        ]);

        foreach ($storyObjects as $index => $storyObject) {
            try {
                $serializedContent = $this->serializer->serialize($storyObject);
                $contentHash = hash('sha256', $serializedContent);

                // Check if content has changed
                $shouldIndex = $force;
                if (!$force) {
                    $existing = $this->vectorStore->findByEntityId($storyObject->getId());
                    $shouldIndex = !$existing || $existing->contentHash !== $contentHash;
                }

                if ($shouldIndex) {
                    $this->indexStoryObject($storyObject, true);
                    $stats['indexed']++;
                } else {
                    $stats['skipped']++;
                }

                if ($progressCallback) {
                    $progressCallback($index + 1, $total, $storyObject);
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                $this->logger?->error('Error indexing story object', [
                    'story_object_id' => $storyObject->getId()->toRfc4122(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger?->info('LARP reindex completed', [
            'larp_id' => $larp->getId()->toRfc4122(),
            'stats' => $stats,
        ]);

        return $stats;
    }

    /**
     * Delete embedding for a story object from the vector store.
     */
    public function deleteStoryObjectEmbedding(StoryObject $storyObject): void
    {
        $this->vectorStore->delete($storyObject->getId());

        $this->logger?->debug('Embedding deleted', [
            'story_object_id' => $storyObject->getId()->toRfc4122(),
            'vector_store' => $this->vectorStore->getProviderName(),
        ]);
    }

    /**
     * Generate embedding for arbitrary text (for queries).
     *
     * @return array<int, float>
     */
    public function generateQueryEmbedding(string $query): array
    {
        return $this->embeddingProvider->embed($query);
    }

    /**
     * Check if vector store is available.
     */
    public function isVectorStoreAvailable(): bool
    {
        return $this->vectorStore->isAvailable();
    }

    /**
     * Get the vector store provider name.
     */
    public function getVectorStoreProvider(): string
    {
        return $this->vectorStore->getProviderName();
    }
}
