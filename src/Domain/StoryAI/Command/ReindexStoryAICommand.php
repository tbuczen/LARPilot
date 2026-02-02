<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Command;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\Message\ReindexLarpMessage;
use App\Domain\StoryAI\Service\Embedding\EmbeddingService;
use App\Domain\StoryAI\Service\VectorStore\VectorStoreInterface;
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
    description: 'Reindex LARP story objects for AI search',
)]
final class ReindexStoryAICommand extends Command
{
    public function __construct(
        private readonly EmbeddingService $embeddingService,
        private readonly EntityManagerInterface $entityManager,
        private readonly VectorStoreInterface $vectorStore,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('larp-id', InputArgument::REQUIRED, 'The LARP ID to reindex (UUID)')
            ->addOption('async', 'a', InputOption::VALUE_NONE, 'Process indexing asynchronously via messenger')
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

        // Show vector store status
        $io->info([
            sprintf('Vector store: %s', $this->vectorStore->getProviderName()),
            sprintf('Available: %s', $this->vectorStore->isAvailable() ? 'Yes' : 'No'),
        ]);

        if (!$this->vectorStore->isAvailable()) {
            $io->error('Vector store is not available. Check your configuration.');
            return Command::FAILURE;
        }

        if ($input->getOption('async')) {
            return $this->processAsync($io, $larp);
        }

        return $this->processSync($io, $larp, $input->getOption('force'));
    }

    private function processAsync(SymfonyStyle $io, Larp $larp): int
    {
        $io->section('Dispatching async reindex message');

        $this->messageBus->dispatch(new ReindexLarpMessage($larp->getId()));
        $io->success('Reindex message dispatched. Run messenger:consume to process.');

        return Command::SUCCESS;
    }

    private function processSync(SymfonyStyle $io, Larp $larp, bool $force): int
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
            },
            $force
        );

        $progressBar->finish();
        $io->newLine(2);

        $io->success([
            sprintf('Indexed: %d', $stats['indexed']),
            sprintf('Skipped (unchanged): %d', $stats['skipped']),
            sprintf('Errors: %d', $stats['errors']),
        ]);

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
