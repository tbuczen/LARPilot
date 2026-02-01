<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\Embedding;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\DTO\VectorDocument;
use App\Domain\StoryAI\Entity\LarpLoreDocument;
use App\Domain\StoryAI\Entity\LoreDocumentChunk;
use App\Domain\StoryAI\Entity\StoryObjectEmbedding;
use App\Domain\StoryAI\Repository\LoreDocumentChunkRepository;
use App\Domain\StoryAI\Repository\StoryObjectEmbeddingRepository;
use App\Domain\StoryAI\Service\Provider\EmbeddingProviderInterface;
use App\Domain\StoryAI\Service\VectorStore\VectorStoreInterface;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Service for generating and managing embeddings.
 *
 * This service follows CQRS principles:
 * - Write operations go to external vector store (Supabase, etc.)
 * - Local StoryObjectEmbedding entities are kept for tracking/metadata only
 */
class EmbeddingService
{
    // Maximum characters per chunk for lore documents
    private const CHUNK_SIZE = 2000;
    // Overlap between chunks for context continuity
    private const CHUNK_OVERLAP = 200;

    public function __construct(
        private readonly EmbeddingProviderInterface $embeddingProvider,
        private readonly StoryObjectSerializer $serializer,
        private readonly VectorStoreInterface $vectorStore,
        private readonly StoryObjectEmbeddingRepository $embeddingRepository,
        private readonly LoreDocumentChunkRepository $chunkRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Index a story object by generating and storing its embedding.
     */
    public function indexStoryObject(StoryObject $storyObject): StoryObjectEmbedding
    {
        $larp = $storyObject->getLarp();
        if (!$larp) {
            throw new \InvalidArgumentException('Story object must belong to a LARP');
        }

        // Serialize the story object to text
        $serializedContent = $this->serializer->serialize($storyObject);
        $contentHash = hash('sha256', $serializedContent);

        // Check if embedding already exists and content hasn't changed
        $existingEmbedding = $this->embeddingRepository->findByStoryObject($storyObject);

        if ($existingEmbedding && $existingEmbedding->getContentHash() === $contentHash) {
            $this->logger?->debug('Content unchanged, skipping re-embedding', [
                'story_object_id' => $storyObject->getId()->toRfc4122(),
            ]);
            return $existingEmbedding;
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

        // Update or create local tracking entity
        $embedding = $existingEmbedding ?? new StoryObjectEmbedding();
        if (!$existingEmbedding) {
            $embedding->setLarp($larp);
            $embedding->setStoryObject($storyObject);
        }

        $embedding->setSerializedContent($serializedContent);
        $embedding->setEmbedding($vector);
        $embedding->setEmbeddingModel($this->embeddingProvider->getModelName());
        $embedding->setTokenCount($this->embeddingProvider->estimateTokenCount($serializedContent));

        $this->entityManager->persist($embedding);
        $this->entityManager->flush();

        $this->logger?->info('Story object indexed successfully', [
            'story_object_id' => $storyObject->getId()->toRfc4122(),
            'type' => $entityType,
            'token_count' => $embedding->getTokenCount(),
            'vector_store' => $this->vectorStore->getProviderName(),
        ]);

        return $embedding;
    }

    /**
     * Index a lore document by chunking and generating embeddings.
     */
    public function indexLoreDocument(LarpLoreDocument $document): void
    {
        $larp = $document->getLarp();
        if (!$larp) {
            throw new \InvalidArgumentException('Lore document must belong to a LARP');
        }

        // Delete existing chunks from vector store
        $this->vectorStore->deleteByFilter([
            'larp_id' => $larp->getId(),
            'type' => VectorDocument::TYPE_LORE_CHUNK,
            'metadata->>document_id' => $document->getId()->toRfc4122(),
        ]);

        // Clear local chunks
        $this->chunkRepository->deleteByDocument($document);
        $document->clearChunks();

        // Chunk the content
        $chunks = $this->chunkContent($document->getContent());

        $this->logger?->debug('Chunking lore document', [
            'document_id' => $document->getId()->toRfc4122(),
            'chunk_count' => count($chunks),
        ]);

        // Add document context to each chunk
        $contextPrefix = sprintf(
            "[%s] %s\n\n",
            $document->getType()->getLabel(),
            $document->getTitle()
        );

        // Generate embeddings in batch
        $textsToEmbed = array_map(
            fn (string $chunk) => $contextPrefix . $chunk,
            $chunks
        );

        $embeddings = $this->embeddingProvider->embedBatch($textsToEmbed);

        // Prepare vector documents for batch upsert
        $vectorDocuments = [];

        // Create chunk entities and vector documents
        foreach ($chunks as $index => $chunkContent) {
            $chunkId = Uuid::v4();

            // Create local chunk entity
            $chunk = new LoreDocumentChunk();
            $chunk->setDocument($document);
            $chunk->setLarp($larp);
            $chunk->setContent($chunkContent);
            $chunk->setChunkIndex($index);
            $chunk->setEmbedding($embeddings[$index]);
            $chunk->setEmbeddingModel($this->embeddingProvider->getModelName());
            $chunk->setTokenCount($this->embeddingProvider->estimateTokenCount($chunkContent));

            $document->addChunk($chunk);
            $this->entityManager->persist($chunk);

            // Create vector document for external store
            $vectorDocuments[] = VectorDocument::forLoreChunk(
                entityId: $chunk->getId(),
                larpId: $larp->getId(),
                documentTitle: $document->getTitle(),
                chunkContent: $chunkContent,
                chunkIndex: $index,
                embedding: $embeddings[$index],
                embeddingModel: $this->embeddingProvider->getModelName(),
                metadata: [
                    'document_id' => $document->getId()->toRfc4122(),
                    'document_type' => $document->getType()->value,
                ],
            );
        }

        // Batch upsert to vector store
        $this->vectorStore->upsertBatch($vectorDocuments);

        $this->entityManager->flush();

        $this->logger?->info('Lore document indexed successfully', [
            'document_id' => $document->getId()->toRfc4122(),
            'chunk_count' => count($chunks),
            'vector_store' => $this->vectorStore->getProviderName(),
        ]);
    }

    /**
     * Reindex all story objects for a LARP.
     *
     * @return array{indexed: int, skipped: int, errors: int}
     */
    public function reindexLarp(Larp $larp, callable $progressCallback = null): array
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
                $existingEmbedding = $this->embeddingRepository->findByStoryObject($storyObject);

                if ($existingEmbedding && $existingEmbedding->getContentHash() === $contentHash) {
                    $stats['skipped']++;
                } else {
                    $this->indexStoryObject($storyObject);
                    $stats['indexed']++;
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
     * Delete embedding for a story object.
     */
    public function deleteStoryObjectEmbedding(StoryObject $storyObject): void
    {
        // Delete from external vector store
        $this->vectorStore->delete($storyObject->getId());

        // Delete local tracking entity
        $embedding = $this->embeddingRepository->findByStoryObject($storyObject);
        if ($embedding) {
            $this->entityManager->remove($embedding);
            $this->entityManager->flush();

            $this->logger?->debug('Embedding deleted', [
                'story_object_id' => $storyObject->getId()->toRfc4122(),
                'vector_store' => $this->vectorStore->getProviderName(),
            ]);
        }
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

    /**
     * Chunk content into overlapping segments.
     *
     * @return array<string>
     */
    private function chunkContent(string $content): array
    {
        $content = trim($content);
        $length = strlen($content);

        if ($length <= self::CHUNK_SIZE) {
            return [$content];
        }

        $chunks = [];
        $position = 0;

        while ($position < $length) {
            $chunkEnd = min($position + self::CHUNK_SIZE, $length);

            // Try to break at a sentence or paragraph boundary
            if ($chunkEnd < $length) {
                $breakpoint = $this->findBreakpoint($content, $chunkEnd);
                if ($breakpoint > $position) {
                    $chunkEnd = $breakpoint;
                }
            }

            $chunk = substr($content, $position, $chunkEnd - $position);
            $chunks[] = trim($chunk);

            // Move position with overlap
            $position = $chunkEnd - self::CHUNK_OVERLAP;
            if ($position <= 0 || $chunkEnd >= $length) {
                break;
            }
        }

        return array_filter($chunks, fn ($chunk) => !empty($chunk));
    }

    /**
     * Find a good breakpoint near the target position.
     */
    private function findBreakpoint(string $content, int $targetPosition): int
    {
        // Look backwards for a paragraph or sentence break
        $searchRange = min(200, $targetPosition);
        $searchStart = $targetPosition - $searchRange;

        $substring = substr($content, $searchStart, $searchRange);

        // Try a paragraph break first
        $lastParagraph = strrpos($substring, "\n\n");
        if ($lastParagraph !== false) {
            return $searchStart + $lastParagraph + 2;
        }

        // Try sentence break
        $lastSentence = max(
            strrpos($substring, '. ') ?: 0,
            strrpos($substring, '! ') ?: 0,
            strrpos($substring, '? ') ?: 0
        );
        if ($lastSentence > 0) {
            return $searchStart + $lastSentence + 2;
        }

        // Try newline
        $lastNewline = strrpos($substring, "\n");
        if ($lastNewline !== false) {
            return $searchStart + $lastNewline + 1;
        }

        // Try space
        $lastSpace = strrpos($substring, ' ');
        if ($lastSpace !== false) {
            return $searchStart + $lastSpace + 1;
        }

        return $targetPosition;
    }
}
