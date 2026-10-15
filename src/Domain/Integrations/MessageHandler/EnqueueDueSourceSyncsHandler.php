<?php

declare(strict_types=1);

namespace App\Domain\Integrations\MessageHandler;

use App\Domain\Integrations\Message\EnqueueDueSourceSyncsMessage;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\Integrations\Service\Capability\SourceSyncIntegrationInterface;
use App\Domain\Integrations\Service\IntegrationRegistry;
use App\Domain\Integrations\Service\Sync\SourceSyncIntervalPolicy;
use App\Domain\Integrations\Service\Sync\SyncNowDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class EnqueueDueSourceSyncsHandler
{
    public const BATCH_SIZE = 200;

    public function __construct(
        private ExternalSourceRepository $externalSourceRepository,
        private SyncNowDispatcher $syncNowDispatcher,
        private IntegrationRegistry $integrationRegistry,
        private SourceSyncIntervalPolicy $syncIntervalPolicy,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(EnqueueDueSourceSyncsMessage $message): int
    {
        $now = $this->clock->now();
        $dueSources = $this->externalSourceRepository->findDueForSync(
            $now,
            $this->integrationRegistry->providersSupporting(SourceSyncIntegrationInterface::class),
            self::BATCH_SIZE,
        );
        if ($dueSources === []) {
            return 0;
        }

        foreach ($dueSources as $dueSource) {
            $dueSource->setNextSyncAt($this->syncIntervalPolicy->nextSyncAt($dueSource, $now));
        }
        $this->entityManager->flush();

        $enqueuedCount = 0;
        foreach ($dueSources as $dueSource) {
            if ($this->syncNowDispatcher->dispatch($dueSource)) {
                ++$enqueuedCount;
            }
        }

        return $enqueuedCount;
    }
}
