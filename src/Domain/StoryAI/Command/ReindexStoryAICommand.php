<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Command;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\Message\ReindexLarpMessage;
use App\Domain\StoryAI\Service\Embedding\EmbeddingService;
use App\Domain\StoryAI\Service\Embedding\StoryObjectSerializer;
use App\Domain\StoryAI\Service\Provider\EmbeddingProviderInterface;
use App\Domain\StoryAI\Service\VectorStore\VectorStoreInterface;
use App\Domain\StoryObject\Entity\StoryObject;
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
        private readonly StoryObjectSerializer $serializer,
        private readonly EmbeddingProviderInterface $embeddingProvider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('larp-id', InputArgument::REQUIRED, 'The LARP ID to reindex (UUID)')
            ->addOption('async', 'a', InputOption::VALUE_NONE, 'Process indexing asynchronously via messenger')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force reindex even if content unchanged')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Test serialization and embedding without storing (for local testing)')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit number of objects to process (useful with --dry-run)', '3');
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

        // Dry-run mode: test pipeline without vector store
        if ($input->getOption('dry-run')) {
            return $this->processDryRun($io, $larp, (int) $input->getOption('limit'));
        }

        // Show vector store status
        $io->info([
            sprintf('Vector store: %s', $this->vectorStore->getProviderName()),
            sprintf('Available: %s', $this->vectorStore->isAvailable() ? 'Yes' : 'No'),
        ]);

        if (!$this->vectorStore->isAvailable()) {
            $io->error('Vector store is not available. Check your configuration.');
            $io->note('Use --dry-run to test serialization and embedding generation locally.');
            return Command::FAILURE;
        }

        if ($input->getOption('async')) {
            return $this->processAsync($io, $larp);
        }

        return $this->processSync($io, $larp, $input->getOption('force'));
    }

    private function processDryRun(SymfonyStyle $io, Larp $larp, int $limit): int
    {
        $io->section('DRY RUN: Testing serialization and embedding generation');
        $io->note('This will call OpenAI for embeddings but will NOT store anything.');

        $storyObjects = $this->entityManager
            ->getRepository(StoryObject::class)
            ->findBy(['larp' => $larp], limit: $limit);

        if (empty($storyObjects)) {
            $io->warning('No story objects found for this LARP.');
            return Command::SUCCESS;
        }

        $io->text(sprintf('Processing %d story objects...', count($storyObjects)));
        $io->newLine();

        $success = 0;
        $errors = 0;

        foreach ($storyObjects as $storyObject) {
            $type = (new \ReflectionClass($storyObject))->getShortName();
            $io->section(sprintf('[%s] %s', $type, $storyObject->getTitle()));

            try {
                // Step 1: Serialize
                $io->text('1. Serializing...');
                $serialized = $this->serializer->serialize($storyObject);
                $charCount = strlen($serialized);
                $io->text(sprintf('   Serialized: %d characters', $charCount));

                // Show preview
                $preview = substr($serialized, 0, 200);
                if (strlen($serialized) > 200) {
                    $preview .= '...';
                }
                $io->text('   Preview:');
                $io->block($preview, null, 'fg=gray');

                // Step 2: Generate embedding
                $io->text('2. Generating embedding via OpenAI...');
                $embedding = $this->embeddingProvider->embed($serialized);
                $io->text(sprintf('   Embedding: %d dimensions', count($embedding)));
                $io->text(sprintf('   First 5 values: [%s]', implode(', ', array_map(
                    fn ($v) => number_format($v, 6),
                    array_slice($embedding, 0, 5)
                ))));

                $io->success('OK');
                $success++;
            } catch (\Throwable $e) {
                $io->error(sprintf('Error: %s', $e->getMessage()));
                $errors++;
            }
        }

        $io->newLine();
        $io->section('Summary');
        $io->listing([
            sprintf('Success: %d', $success),
            sprintf('Errors: %d', $errors),
        ]);

        if ($errors === 0) {
            $io->success('Dry run completed successfully! Your pipeline is working.');
            $io->note('To actually index, configure VECTOR_STORE_DSN and run without --dry-run.');
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
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
