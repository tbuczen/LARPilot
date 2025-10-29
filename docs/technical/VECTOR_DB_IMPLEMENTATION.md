# Vector Database Implementation Guide

This guide provides step-by-step instructions for implementing vector database functionality for LARP context storage and semantic search.

## Overview

The vector database stores embeddings of LARP story objects (characters, threads, quests, events) to enable:
- Semantic search (find similar content)
- Efficient context retrieval for AI
- Relevance ranking
- Content recommendations

## Architecture Decision

We recommend starting with **PostgreSQL + pgvector** for simplicity:
- Uses existing database infrastructure
- No additional services to manage
- Good performance for moderate scale (up to 100K vectors)
- Easy to migrate to specialized vector DB later

## Phase 1: PostgreSQL with pgvector

### Step 1: Install pgvector Extension

**On local development (Docker)**:

1. Update `docker-compose.yml`:
```yaml
services:
  db:
    image: pgvector/pgvector:pg16
    # ... existing config
```

2. Restart database:
```bash
make stop
make start
```

3. Enable extension:
```sql
CREATE EXTENSION IF NOT EXISTS vector;
```

**On production**:
Follow pgvector installation guide for your PostgreSQL version: https://github.com/pgvector/pgvector#installation

### Step 2: Create Embedding Table

Create migration:
```bash
php bin/console make:migration
```

Add to migration file:
```php
public function up(Schema $schema): void
{
    $this->addSql('CREATE EXTENSION IF NOT EXISTS vector');

    $this->addSql('
        CREATE TABLE larp_embedding (
            id SERIAL PRIMARY KEY,
            larp_id INT NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT NOT NULL,
            content TEXT NOT NULL,
            metadata JSONB,
            embedding vector(1536),
            created_at TIMESTAMP DEFAULT NOW(),
            updated_at TIMESTAMP DEFAULT NOW(),
            CONSTRAINT fk_larp FOREIGN KEY (larp_id)
                REFERENCES larp(id) ON DELETE CASCADE
        )
    ');

    // Create indexes
    $this->addSql('CREATE INDEX idx_larp_embedding_larp ON larp_embedding(larp_id)');
    $this->addSql('CREATE INDEX idx_larp_embedding_entity ON larp_embedding(entity_type, entity_id)');
    $this->addSql('CREATE INDEX idx_larp_embedding_vector ON larp_embedding USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
}

public function down(Schema $schema): void
{
    $this->addSql('DROP TABLE IF EXISTS larp_embedding');
}
```

Run migration:
```bash
php bin/console doctrine:migrations:migrate
```

### Step 3: Create Entity and Repository

**Entity**: `src/Domain/StoryAI/Entity/LarpEmbedding.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\Repository\LarpEmbeddingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LarpEmbeddingRepository::class)]
#[ORM\Table(name: 'larp_embedding')]
class LarpEmbedding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Larp $larp;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $entityType; // 'character', 'thread', 'quest', 'event'

    #[ORM\Column(type: Types::INTEGER)]
    private int $entityId;

    #[ORM\Column(type: Types::TEXT)]
    private string $content; // Original text that was embedded

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null; // Additional context

    // Note: embedding is stored as binary, accessed via native queries

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    // Getters and setters...
}
```

**Repository**: `src/Domain/StoryAI/Repository/LarpEmbeddingRepository.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Infrastructure\Repository\BaseRepository;
use App\Domain\StoryAI\Entity\LarpEmbedding;
use Doctrine\Persistence\ManagerRegistry;

class LarpEmbeddingRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpEmbedding::class);
    }

    /**
     * Insert embedding (requires native SQL for vector type).
     */
    public function insertEmbedding(
        Larp $larp,
        string $entityType,
        int $entityId,
        string $content,
        array $embedding,
        ?array $metadata = null,
    ): void {
        $conn = $this->getEntityManager()->getConnection();

        // Delete existing embedding for this entity
        $conn->executeStatement(
            'DELETE FROM larp_embedding WHERE larp_id = :larp_id AND entity_type = :type AND entity_id = :id',
            [
                'larp_id' => $larp->getId(),
                'type' => $entityType,
                'id' => $entityId,
            ]
        );

        // Insert new embedding
        $conn->executeStatement(
            'INSERT INTO larp_embedding (larp_id, entity_type, entity_id, content, metadata, embedding, created_at, updated_at)
             VALUES (:larp_id, :type, :entity_id, :content, :metadata, :embedding, NOW(), NOW())',
            [
                'larp_id' => $larp->getId(),
                'type' => $entityType,
                'entity_id' => $entityId,
                'content' => $content,
                'metadata' => json_encode($metadata ?? []),
                'embedding' => '[' . implode(',', $embedding) . ']', // pgvector format
            ]
        );
    }

    /**
     * Find similar embeddings using cosine similarity.
     *
     * @return array Array of results with entity info and similarity score
     */
    public function findSimilar(Larp $larp, array $queryEmbedding, int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                id,
                entity_type,
                entity_id,
                content,
                metadata,
                1 - (embedding <=> :query_embedding) as similarity
            FROM larp_embedding
            WHERE larp_id = :larp_id
            ORDER BY embedding <=> :query_embedding
            LIMIT :limit
        ";

        $result = $conn->executeQuery($sql, [
            'larp_id' => $larp->getId(),
            'query_embedding' => '[' . implode(',', $queryEmbedding) . ']',
            'limit' => $limit,
        ]);

        return $result->fetchAllAssociative();
    }

    /**
     * Delete all embeddings for a LARP (for rebuild).
     */
    public function deleteByLarp(Larp $larp): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->executeStatement(
            'DELETE FROM larp_embedding WHERE larp_id = :larp_id',
            ['larp_id' => $larp->getId()]
        );
    }
}
```

### Step 4: Implement Embedding Generation Service

**Service**: `src/Domain/StoryAI/Service/EmbeddingService.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Generates embeddings using OpenAI or free alternatives.
 */
class EmbeddingService
{
    private const OPENAI_MODEL = 'text-embedding-3-small';
    private const OPENAI_DIMENSIONS = 1536;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $openaiApiKey,
        private readonly string $embeddingProvider, // 'openai' or 'local'
    ) {
    }

    /**
     * Generate embedding vector for text.
     *
     * @return float[] Vector of 1536 dimensions
     */
    public function generateEmbedding(string $text): array
    {
        if ($this->embeddingProvider === 'openai') {
            return $this->generateOpenAIEmbedding($text);
        }

        // @TODO: Implement local embedding using sentence-transformers
        throw new \RuntimeException('Local embeddings not yet implemented');
    }

    /**
     * Generate embeddings for multiple texts in batch.
     *
     * @param string[] $texts
     * @return array<int, float[]> Array of embedding vectors
     */
    public function generateBatchEmbeddings(array $texts): array
    {
        if ($this->embeddingProvider === 'openai') {
            return $this->generateOpenAIBatchEmbeddings($texts);
        }

        throw new \RuntimeException('Local batch embeddings not yet implemented');
    }

    private function generateOpenAIEmbedding(string $text): array
    {
        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/embeddings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => self::OPENAI_MODEL,
                'input' => $text,
            ],
        ]);

        $data = $response->toArray();

        if (!isset($data['data'][0]['embedding'])) {
            throw new \RuntimeException('Invalid OpenAI embedding response');
        }

        return $data['data'][0]['embedding'];
    }

    private function generateOpenAIBatchEmbeddings(array $texts): array
    {
        if (count($texts) > 100) {
            throw new \InvalidArgumentException('OpenAI supports max 100 texts per batch');
        }

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/embeddings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => self::OPENAI_MODEL,
                'input' => $texts,
            ],
        ]);

        $data = $response->toArray();

        if (!isset($data['data'])) {
            throw new \RuntimeException('Invalid OpenAI embedding response');
        }

        $embeddings = [];
        foreach ($data['data'] as $item) {
            $embeddings[$item['index']] = $item['embedding'];
        }

        return $embeddings;
    }
}
```

**Configuration**: `config/services.yaml`

```yaml
services:
    App\Domain\StoryAI\Service\EmbeddingService:
        arguments:
            $openaiApiKey: '%env(OPENAI_API_KEY)%'
            $embeddingProvider: '%env(default:openai:EMBEDDING_PROVIDER)%'
```

### Step 5: Implement Context Builder

**Service**: `src/Domain/StoryAI/Service/ContextBuilderService.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\Repository\LarpEmbeddingRepository;
use App\Domain\StoryObject\Repository\CharacterRepository;
use App\Domain\StoryObject\Repository\ThreadRepository;
use App\Domain\StoryObject\Repository\QuestRepository;
use App\Domain\StoryObject\Repository\EventRepository;

/**
 * Builds and maintains LARP context in vector database.
 */
class ContextBuilderService
{
    public function __construct(
        private readonly LarpEmbeddingRepository $embeddingRepository,
        private readonly EmbeddingService $embeddingService,
        private readonly CharacterRepository $characterRepository,
        private readonly ThreadRepository $threadRepository,
        private readonly QuestRepository $questRepository,
        private readonly EventRepository $eventRepository,
    ) {
    }

    /**
     * Build complete context for a LARP.
     * This processes all story objects and stores their embeddings.
     */
    public function buildContext(Larp $larp): void
    {
        // Clear existing embeddings
        $this->embeddingRepository->deleteByLarp($larp);

        // Process characters
        $characters = $this->characterRepository->findBy(['larp' => $larp]);
        foreach ($characters as $character) {
            $content = $this->buildCharacterContent($character);
            $embedding = $this->embeddingService->generateEmbedding($content);

            $this->embeddingRepository->insertEmbedding(
                larp: $larp,
                entityType: 'character',
                entityId: $character->getId(),
                content: $content,
                embedding: $embedding,
                metadata: [
                    'title' => $character->getTitle(),
                    'tags' => $character->getTags(),
                ],
            );
        }

        // Process threads
        $threads = $this->threadRepository->findBy(['larp' => $larp]);
        foreach ($threads as $thread) {
            $content = $this->buildThreadContent($thread);
            $embedding = $this->embeddingService->generateEmbedding($content);

            $this->embeddingRepository->insertEmbedding(
                larp: $larp,
                entityType: 'thread',
                entityId: $thread->getId(),
                content: $content,
                embedding: $embedding,
                metadata: [
                    'title' => $thread->getTitle(),
                    'tags' => $thread->getTags(),
                ],
            );
        }

        // @TODO: Process quests, events, factions, items, places
    }

    /**
     * Update embeddings for a single entity.
     */
    public function updateEntity(Larp $larp, string $entityType, int $entityId): void
    {
        // @TODO: Implement incremental update
        // Similar to buildContext but for single entity
    }

    /**
     * Find relevant context using semantic search.
     *
     * @return array Array of relevant story objects
     */
    public function findRelevantContext(Larp $larp, string $query, int $limit = 20): array
    {
        // Generate embedding for query
        $queryEmbedding = $this->embeddingService->generateEmbedding($query);

        // Search similar embeddings
        return $this->embeddingRepository->findSimilar($larp, $queryEmbedding, $limit);
    }

    private function buildCharacterContent($character): string
    {
        $parts = [
            'Title: ' . $character->getTitle(),
            'Description: ' . strip_tags($character->getDescription() ?? ''),
            'Backstory: ' . strip_tags($character->getBackstory() ?? ''),
        ];

        if (!empty($character->getTags())) {
            $parts[] = 'Tags: ' . implode(', ', $character->getTags());
        }

        // @TODO: Add skills, relationships, etc.

        return implode("\n", $parts);
    }

    private function buildThreadContent($thread): string
    {
        $parts = [
            'Title: ' . $thread->getTitle(),
            'Summary: ' . strip_tags($thread->getSummary() ?? ''),
        ];

        // @TODO: Add acts, characters, etc.

        return implode("\n", $parts);
    }
}
```

### Step 6: Create Console Command for Context Building

**Command**: `src/Domain/StoryAI/Command/BuildContextCommand.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Command;

use App\Domain\Core\Repository\LarpRepository;
use App\Domain\StoryAI\Service\ContextBuilderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:story-ai:build-context',
    description: 'Build vector DB context for a LARP',
)]
class BuildContextCommand extends Command
{
    public function __construct(
        private readonly LarpRepository $larpRepository,
        private readonly ContextBuilderService $contextBuilder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('larp-id', InputArgument::REQUIRED, 'LARP ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $larpId = (int) $input->getArgument('larp-id');

        $larp = $this->larpRepository->find($larpId);
        if (!$larp) {
            $io->error('LARP not found');
            return Command::FAILURE;
        }

        $io->info(sprintf('Building context for LARP: %s', $larp->getTitle()));

        try {
            $this->contextBuilder->buildContext($larp);
            $io->success('Context built successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to build context: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
```

**Usage**:
```bash
php bin/console app:story-ai:build-context 1
```

### Step 7: Set Up Event Listeners for Incremental Updates

**Event Listener**: `src/Domain/StoryAI/EventListener/StoryObjectEmbeddingListener.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\EventListener;

use App\Domain\StoryAI\Service\ContextBuilderService;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Thread;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

/**
 * Automatically updates embeddings when story objects change.
 *
 * @TODO: Make this async using Symfony Messenger (Phase 2)
 */
#[AsEntityListener(event: Events::postPersist, entity: Character::class)]
#[AsEntityListener(event: Events::postUpdate, entity: Character::class)]
#[AsEntityListener(event: Events::postPersist, entity: Thread::class)]
#[AsEntityListener(event: Events::postUpdate, entity: Thread::class)]
class StoryObjectEmbeddingListener
{
    public function __construct(
        private readonly ContextBuilderService $contextBuilder,
    ) {
    }

    public function postPersist(object $entity): void
    {
        $this->updateEmbedding($entity);
    }

    public function postUpdate(object $entity): void
    {
        $this->updateEmbedding($entity);
    }

    private function updateEmbedding(object $entity): void
    {
        if (!method_exists($entity, 'getLarp') || !method_exists($entity, 'getId')) {
            return;
        }

        $larp = $entity->getLarp();
        $entityType = match (get_class($entity)) {
            Character::class => 'character',
            Thread::class => 'thread',
            // @TODO: Add more entity types
            default => null,
        };

        if ($entityType && $larp && $entity->getId()) {
            $this->contextBuilder->updateEntity($larp, $entityType, $entity->getId());
        }
    }
}
```

## Testing the Implementation

### 1. Test Embedding Generation

```php
// In a test or controller
$embedding = $embeddingService->generateEmbedding('Test character description');
dump(count($embedding)); // Should be 1536
dump($embedding[0]); // Should be a float between -1 and 1
```

### 2. Test Context Building

```bash
# Build context for LARP ID 1
php bin/console app:story-ai:build-context 1

# Check database
psql -U larpilot -d larpilot -c "SELECT COUNT(*) FROM larp_embedding WHERE larp_id = 1;"
```

### 3. Test Semantic Search

```php
// In a controller or test
$results = $contextBuilder->findRelevantContext($larp, 'brave warrior character', limit: 5);
foreach ($results as $result) {
    echo sprintf("%s: %s (similarity: %.2f)\n",
        $result['entity_type'],
        $result['content'],
        $result['similarity']
    );
}
```

## Cost Optimization

### Embedding Generation Costs (OpenAI)

- Model: `text-embedding-3-small`
- Cost: **$0.00002 per 1K tokens** (~$0.02 per 1M tokens)
- Average character description: ~500 tokens = **$0.00001** (~$0.01 per 1000 characters)

**Example LARP**:
- 100 characters × 500 tokens = 50K tokens = **$0.001**
- 50 threads × 300 tokens = 15K tokens = **$0.0003**
- 100 quests × 200 tokens = 20K tokens = **$0.0004**
- **Total: ~$0.002** (less than 1 cent!)

### Optimization Strategies

1. **Cache embeddings**: Never regenerate unless content changes
2. **Batch API calls**: Use batch endpoint for bulk processing
3. **Debounce updates**: Wait 1 minute after edit before regenerating
4. **Use local embeddings** for development (sentence-transformers)

## Migration to Specialized Vector DB (Phase 2)

When you need better performance or scale:

### Option A: ChromaDB (Self-Hosted)

1. Add to `docker-compose.yml`:
```yaml
chroma:
  image: ghcr.io/chroma-core/chroma:latest
  ports:
    - "8000:8000"
  volumes:
    - chroma_data:/chroma/chroma
  environment:
    - IS_PERSISTENT=TRUE
```

2. Create `VectorStoreInterface`:
```php
interface VectorStoreInterface
{
    public function insert(string $id, array $embedding, array $metadata): void;
    public function search(array $queryEmbedding, int $limit): array;
    public function delete(string $id): void;
}
```

3. Implement `ChromaDBStore` and `PgVectorStore`

4. Update services to use interface instead of repository directly

### Option B: Pinecone (Managed)

Similar approach, implement `PineconeStore` class using Pinecone PHP SDK.

## Troubleshooting

### Issue: pgvector extension not available

**Solution**: Check PostgreSQL version (requires 11+) and install pgvector:
```bash
# Ubuntu/Debian
sudo apt install postgresql-16-pgvector

# macOS
brew install pgvector

# Docker
Use pgvector/pgvector:pg16 image
```

### Issue: Slow vector search

**Solution**: Ensure index is created:
```sql
CREATE INDEX idx_larp_embedding_vector
ON larp_embedding
USING ivfflat (embedding vector_cosine_ops)
WITH (lists = 100);
```

Adjust `lists` parameter based on table size (rule of thumb: sqrt(row_count)).

### Issue: Out of memory during batch processing

**Solution**: Process in smaller batches:
```php
$characters = $characterRepository->findBy(['larp' => $larp]);
$batches = array_chunk($characters, 10); // Process 10 at a time

foreach ($batches as $batch) {
    // Process batch
    $entityManager->clear(); // Clear memory
}
```

## Next Steps

1. **Phase 1**: Implement basic embedding generation and storage
2. **Phase 2**: Add incremental updates via event listeners
3. **Phase 3**: Integrate with AI prompt building
4. **Phase 4**: Add UI for context rebuilding
5. **Phase 5**: Optimize performance and cost tracking

## References

- [pgvector Documentation](https://github.com/pgvector/pgvector)
- [OpenAI Embeddings Guide](https://platform.openai.com/docs/guides/embeddings)
- [Sentence Transformers](https://www.sbert.net/) (free alternative)
- [ChromaDB Documentation](https://docs.trychroma.com/)

---

**Document Version**: 1.0
**Last Updated**: 2025-10-28
**Status**: Implementation Guide - Ready to Use
