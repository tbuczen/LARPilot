<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\MessageHandler;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\Message\ReindexLarpMessage;
use App\Domain\StoryAI\Service\Embedding\EmbeddingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ReindexLarpHandler
{
    public function __construct(
        private readonly EmbeddingService $embeddingService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(ReindexLarpMessage $message): void
    {
        $larp = $this->entityManager
            ->getRepository(Larp::class)
            ->find($message->larpId);

        if (!$larp) {
            $this->logger?->warning('LARP not found for reindexing', [
                'larp_id' => $message->larpId->toRfc4122(),
            ]);
            return;
        }

        try {
            $stats = $this->embeddingService->reindexLarp($larp);
            $this->logger?->info('LARP reindexed via async handler', [
                'larp_id' => $message->larpId->toRfc4122(),
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            $this->logger?->error('Failed to reindex LARP', [
                'larp_id' => $message->larpId->toRfc4122(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
