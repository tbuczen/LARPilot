<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\StoryAI\Repository\LoreDocumentChunkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

/**
 * A chunk of a lore document with its own embedding.
 * Used for long documents that need to be split for effective retrieval.
 */
#[ORM\Entity(repositoryClass: LoreDocumentChunkRepository::class)]
#[ORM\Table(name: 'lore_document_chunk')]
#[ORM\Index(name: 'idx_chunk_document', columns: ['document_id'])]
#[ORM\Index(name: 'idx_chunk_larp', columns: ['larp_id'])]
#[ORM\Index(name: 'idx_chunk_index', columns: ['chunk_index'])]
class LoreDocumentChunk implements Timestampable
{
    use UuidTraitEntity;
    use TimestampableEntity;

    /**
     * The parent document this chunk belongs to.
     */
    #[ORM\ManyToOne(targetEntity: LarpLoreDocument::class, inversedBy: 'chunks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?LarpLoreDocument $document = null;

    /**
     * The LARP (denormalized for efficient querying).
     */
    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Larp $larp = null;

    /**
     * The chunk content.
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    /**
     * Index of this chunk within the document (0-based).
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $chunkIndex = 0;

    /**
     * Hash of the content for change detection.
     */
    #[ORM\Column(length: 64)]
    private string $contentHash = '';

    /**
     * The vector embedding for this chunk.
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
     * Token count of this chunk.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $tokenCount = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getDocument(): ?LarpLoreDocument
    {
        return $this->document;
    }

    public function setDocument(?LarpLoreDocument $document): self
    {
        $this->document = $document;
        if ($document) {
            $this->larp = $document->getLarp();
        }
        return $this;
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

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        $this->contentHash = hash('sha256', $content);
        return $this;
    }

    public function getChunkIndex(): int
    {
        return $this->chunkIndex;
    }

    public function setChunkIndex(int $chunkIndex): self
    {
        $this->chunkIndex = $chunkIndex;
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
