<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\MessageHandler;

use App\Domain\StoryAI\Entity\LarpLoreDocument;
use App\Domain\StoryAI\Message\IndexLoreDocumentMessage;
use App\Domain\StoryAI\Service\Embedding\EmbeddingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class IndexLoreDocumentHandler
{
    public function __construct(
        private EmbeddingService       $embeddingService,
        private EntityManagerInterface $entityManager,
        private ?LoggerInterface       $logger = null,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function __invoke(IndexLoreDocumentMessage $message): void
    {
        $document = $this->entityManager
            ->getRepository(LarpLoreDocument::class)
            ->find($message->documentId);

        if (!$document) {
            $this->logger?->warning('Lore document not found for indexing', [
                'document_id' => $message->documentId->toRfc4122(),
            ]);
            return;
        }

        try {
            $this->embeddingService->indexLoreDocument($document);
            $this->logger?->info('Lore document indexed via async handler', [
                'document_id' => $message->documentId->toRfc4122(),
            ]);
        } catch (\Throwable $e) {
            $this->logger?->error('Failed to index lore document', [
                'document_id' => $message->documentId->toRfc4122(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
