<?php

namespace App\Domain\StoryAI\EventSubscriber;

use App\Domain\Core\Event\LarpDeletedEvent;
use App\Domain\StoryAI\Service\VectorStore\VectorStoreInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
readonly class RemoveEmbeddingsOfDeletedLarpListener
{
    public function __construct(
        private VectorStoreInterface $vectorStore,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(LarpDeletedEvent $event): void
    {
        $larpId = $event->getLarpId()->toRfc4122();

        try {
            $this->vectorStore->deleteByFilter(['larp_id' => $larpId]);
        } catch (\Throwable $exception) {
            $this->logger->warning('Could not remove embeddings of a deleted LARP.', ['larpId' => $larpId, 'exception' => $exception]);
        }
    }
}
