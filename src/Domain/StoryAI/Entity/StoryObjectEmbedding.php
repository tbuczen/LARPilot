<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\StoryAI\Repository\StoryObjectEmbeddingRepository;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

/**
 * Stores vector embeddings for story objects to enable semantic search.
 * Uses pgvector extension for PostgreSQL similarity search.
 */
#[ORM\Entity(repositoryClass: StoryObjectEmbeddingRepository::class)]
#[ORM\Table(name: 'story_object_embedding')]
#[ORM\Index(columns: ['larp_id'], name: 'idx_embedding_larp')]
#[ORM\Index(columns: ['story_object_id'], name: 'idx_embedding_story_object')]
#[ORM\Index(columns: ['content_hash'], name: 'idx_embedding_content_hash')]
class StoryObjectEmbedding implements Timestampable
{
    use UuidTraitEntity;
    use TimestampableEntity;

    /**
     * The LARP this embedding belongs to (for query scoping).
     */
    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Larp $larp = null;

    /**
     * The story object this embedding represents.
     */
    #[ORM\ManyToOne(targetEntity: StoryObject::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StoryObject $storyObject = null;

    /**
     * The serialized text content that was embedded.
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $serializedContent = '';

    /**
     * Hash of the serialized content for change detection.
     */
    #[ORM\Column(length: 64)]
    private string $contentHash = '';

    /**
     * The vector embedding (stored as JSON array, will use pgvector type in migration).
     * Using JSONB for now, will be converted to vector(1536) in migration.
     *
     * @var array<int, float>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $embedding = [];

    /**
     * The model used to generate this embedding.
     */
    #[ORM\Column(length: 100)]
    private string $embeddingModel = 'text-embedding-3-small';

    /**
     * Dimensions of the embedding vector.
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $dimensions = 1536;

    /**
     * Token count of the serialized content.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $tokenCount = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(?Larp $larp): self
    {
        $this->larp = $larp;
        return $this;
    }

    public function getStoryObject(): ?StoryObject
    {
        return $this->storyObject;
    }

    public function setStoryObject(?StoryObject $storyObject): self
    {
        $this->storyObject = $storyObject;
        return $this;
    }

    public function getSerializedContent(): string
    {
        return $this->serializedContent;
    }

    public function setSerializedContent(string $serializedContent): self
    {
        $this->serializedContent = $serializedContent;
        $this->contentHash = hash('sha256', $serializedContent);
        return $this;
    }

    public function getContentHash(): string
    {
        return $this->contentHash;
    }

    /**
     * @return array<int, float>
     */
    public function getEmbedding(): array
    {
        return $this->embedding;
    }

    /**
     * @param array<int, float> $embedding
     */
    public function setEmbedding(array $embedding): self
    {
        $this->embedding = $embedding;
        $this->dimensions = count($embedding);
        return $this;
    }

    public function getEmbeddingModel(): string
    {
        return $this->embeddingModel;
    }

    public function setEmbeddingModel(string $embeddingModel): self
    {
        $this->embeddingModel = $embeddingModel;
        return $this;
    }

    public function getDimensions(): int
    {
        return $this->dimensions;
    }

    public function getTokenCount(): ?int
    {
        return $this->tokenCount;
    }

    public function setTokenCount(?int $tokenCount): self
    {
        $this->tokenCount = $tokenCount;
        return $this;
    }

    /**
     * Check if content has changed by comparing hashes.
     */
    public function hasContentChanged(string $newContent): bool
    {
        return $this->contentHash !== hash('sha256', $newContent);
    }
}
