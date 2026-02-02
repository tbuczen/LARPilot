<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\MessageHandler;

use App\Domain\StoryAI\Message\IndexStoryObjectMessage;
use App\Domain\StoryAI\Service\Embedding\EmbeddingService;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class IndexStoryObjectHandler
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
    public function __invoke(IndexStoryObjectMessage $message): void
    {
        $storyObject = $this->entityManager
            ->getRepository(StoryObject::class)
            ->find($message->storyObjectId);

        if (!$storyObject) {
            $this->logger?->warning('Story object not found for indexing', [
                'story_object_id' => $message->storyObjectId->toRfc4122(),
            ]);
            return;
        }

        try {
            $this->embeddingService->indexStoryObject($storyObject);
            $this->logger?->info('Story object indexed via async handler', [
                'story_object_id' => $message->storyObjectId->toRfc4122(),
            ]);
        } catch (\Throwable $e) {
            $this->logger?->error('Failed to index story object', [
                'story_object_id' => $message->storyObjectId->toRfc4122(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
