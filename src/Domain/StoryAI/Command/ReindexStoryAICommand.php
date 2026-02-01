<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Command;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\Message\ReindexLarpMessage;
use App\Domain\StoryAI\Repository\LarpLoreDocumentRepository;
use App\Domain\StoryAI\Repository\StoryObjectEmbeddingRepository;
use App\Domain\StoryAI\Service\Embedding\EmbeddingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:story-ai:reindex',
    description: 'Reindex LARP content for AI search',
)]
final class ReindexStoryAICommand extends Command
{
    public function __construct(
        private readonly EmbeddingService $embeddingService,
        private readonly EntityManagerInterface $entityManager,
        private readonly StoryObjectEmbeddingRepository $embeddingRepository,
        private readonly LarpLoreDocumentRepository $loreDocumentRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('larp-id', InputArgument::REQUIRED, 'The LARP ID to reindex (UUID)')
            ->addOption('async', 'a', InputOption::VALUE_NONE, 'Process indexing asynchronously via messenger')
            ->addOption('include-lore', 'l', InputOption::VALUE_NONE, 'Also reindex lore documents')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force reindex even if content unchanged');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $larpIdString = $input->getArgument('larp-id');

        try {
            $larpId = Uuid::fromString($larpIdString);
        } catch (\InvalidArgumentException $e) {
            $io->error(sprintf('Invalid UUID format: %s', $larpIdString));
            return Command::FAILURE;
        }

        $larp = $this->entityManager->getRepository(Larp::class)->find($larpId);

        if (!$larp) {
            $io->error(sprintf('LARP not found: %s', $larpIdString));
            return Command::FAILURE;
        }

        $io->title(sprintf('Reindexing LARP: %s', $larp->getTitle()));

        // Show current stats
        $existingCount = $this->embeddingRepository->countByLarp($larp);
        $loreDocCount = $this->loreDocumentRepository->countActiveByLarp($larp);

        $io->info([
            sprintf('Existing embeddings: %d', $existingCount),
            sprintf('Lore documents: %d', $loreDocCount),
        ]);

        if ($input->getOption('async')) {
            return $this->processAsync($io, $larp, $input->getOption('include-lore'));
        }

        return $this->processSync($io, $larp, $input->getOption('include-lore'), $input->getOption('force'));
    }

    private function processAsync(SymfonyStyle $io, Larp $larp, bool $includeLore): int
    {
        $io->section('Dispatching async reindex message');

        $this->messageBus->dispatch(new ReindexLarpMessage($larp->getId()));
        $io->success('Reindex message dispatched. Run messenger:consume to process.');

        if ($includeLore) {
            $io->note('Lore documents will need to be reindexed separately in async mode.');
        }

        return Command::SUCCESS;
    }

    private function processSync(SymfonyStyle $io, Larp $larp, bool $includeLore, bool $force): int
    {
        $io->section('Indexing story objects');

        $progressBar = $io->createProgressBar();
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        $stats = $this->embeddingService->reindexLarp(
            $larp,
            function (int $current, int $total, $storyObject) use ($progressBar) {
                $progressBar->setMaxSteps($total);
                $progressBar->setProgress($current);
                $progressBar->setMessage($storyObject->getTitle());
            }
        );

        $progressBar->finish();
        $io->newLine(2);

        $io->success([
            sprintf('Indexed: %d', $stats['indexed']),
            sprintf('Skipped (unchanged): %d', $stats['skipped']),
            sprintf('Errors: %d', $stats['errors']),
        ]);

        if ($includeLore) {
            $io->section('Indexing lore documents');

            $documents = $this->loreDocumentRepository->findActiveByLarp($larp);

            if (empty($documents)) {
                $io->note('No active lore documents to index.');
            } else {
                $progressBar = $io->createProgressBar(count($documents));
                $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

                $loreStats = ['indexed' => 0, 'errors' => 0];

                foreach ($documents as $document) {
                    $progressBar->setMessage($document->getTitle());

                    try {
                        $this->embeddingService->indexLoreDocument($document);
                        $loreStats['indexed']++;
                    } catch (\Throwable $e) {
                        $loreStats['errors']++;
                        $io->warning(sprintf('Error indexing "%s": %s', $document->getTitle(), $e->getMessage()));
                    }

                    $progressBar->advance();
                }

                $progressBar->finish();
                $io->newLine(2);

                $io->success([
                    sprintf('Lore documents indexed: %d', $loreStats['indexed']),
                    sprintf('Errors: %d', $loreStats['errors']),
                ]);
            }
        }

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
