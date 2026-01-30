<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Entity;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\StoryAI\Entity\Enum\LoreDocumentType;
use App\Domain\StoryAI\Repository\LarpLoreDocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

/**
 * Custom lore/setting documents uploaded by organizers.
 * Used to provide world-building context to the AI assistant.
 */
#[ORM\Entity(repositoryClass: LarpLoreDocumentRepository::class)]
#[ORM\Table(name: 'larp_lore_document')]
#[ORM\Index(name: 'idx_lore_doc_larp', columns: ['larp_id'])]
#[ORM\Index(name: 'idx_lore_doc_priority', columns: ['priority'])]
#[ORM\Index(name: 'idx_lore_doc_type', columns: ['type'])]
class LarpLoreDocument implements Timestampable
{
    use UuidTraitEntity;
    use TimestampableEntity;

    /**
     * The LARP this document belongs to.
     */
    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Larp $larp = null;

    /**
     * Title of the document.
     */
    #[ORM\Column(length: 255)]
    private string $title = '';

    /**
     * Brief description of what this document contains.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * The type/category of the lore document.
     */
    #[ORM\Column(length: 50, enumType: LoreDocumentType::class)]
    private LoreDocumentType $type = LoreDocumentType::GENERAL;

    /**
     * The full content of the document.
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    /**
     * Priority for inclusion in AI context (higher = more important).
     * Documents with higher priority are always included first.
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $priority = 50;

    /**
     * Whether this document should always be included in AI context.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $alwaysInclude = false;

    /**
     * Whether this document is active and should be used.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = true;

    /**
     * User who created/uploaded this document.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    /**
     * Embedded chunks for this document (for long documents).
     *
     * @var Collection<int, LoreDocumentChunk>
     */
    #[ORM\OneToMany(
        targetEntity: LoreDocumentChunk::class,
        mappedBy: 'document',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['chunkIndex' => 'ASC'])]
    private Collection $chunks;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->chunks = new ArrayCollection();
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): LoreDocumentType
    {
        return $this->type;
    }

    public function setType(LoreDocumentType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function isAlwaysInclude(): bool
    {
        return $this->alwaysInclude;
    }

    public function setAlwaysInclude(bool $alwaysInclude): self
    {
        $this->alwaysInclude = $alwaysInclude;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return Collection<int, LoreDocumentChunk>
     */
    public function getChunks(): Collection
    {
        return $this->chunks;
    }

    public function addChunk(LoreDocumentChunk $chunk): self
    {
        if (!$this->chunks->contains($chunk)) {
            $this->chunks->add($chunk);
            $chunk->setDocument($this);
        }
        return $this;
    }

    public function removeChunk(LoreDocumentChunk $chunk): self
    {
        if ($this->chunks->removeElement($chunk)) {
            if ($chunk->getDocument() === $this) {
                $chunk->setDocument(null);
            }
        }
        return $this;
    }

    public function clearChunks(): self
    {
        $this->chunks->clear();
        return $this;
    }

    /**
     * Estimate character count (for chunking decisions).
     */
    public function getContentLength(): int
    {
        return strlen($this->content);
    }
}
