<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Entity;

use App\Domain\Core\Entity\Tag;
use App\Domain\StoryObject\Entity\Enum\LoreDocumentCategory;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Repository\LoreDocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * General lore document for world-building content.
 *
 * Used to store setting information like religion, history, timeline,
 * world rules, culture notes, etc. These documents are indexed for
 * AI semantic search to provide context for story assistance.
 */
#[ORM\Entity(repositoryClass: LoreDocumentRepository::class)]
class LoreDocument extends StoryObject
{
    /**
     * The category of this lore document.
     */
    #[Gedmo\Versioned]
    #[ORM\Column(length: 30, enumType: LoreDocumentCategory::class)]
    private LoreDocumentCategory $category = LoreDocumentCategory::GENERAL;

    /**
     * Priority for AI context inclusion (higher = more important).
     * Documents with higher priority are included first when building AI context.
     */
    #[ORM\Column(type: 'integer', options: ['default' => 50])]
    private int $priority = 50;

    /**
     * Whether this document should always be included in AI context.
     * Use sparingly for critical world-building information.
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $alwaysIncludeInContext = false;

    /**
     * Whether this document is active and should be used/indexed.
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $active = true;

    /**
     * Optional summary for quick reference (shown in lists, used for embedding).
     */
    #[Gedmo\Versioned]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $summary = null;

    /** @var Collection<int, Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'lore_document_tags')]
    private Collection $tags;

    public function __construct()
    {
        parent::__construct();
        $this->tags = new ArrayCollection();
    }

    public function getCategory(): LoreDocumentCategory
    {
        return $this->category;
    }

    public function setCategory(LoreDocumentCategory $category): self
    {
        $this->category = $category;
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

    public function isAlwaysIncludeInContext(): bool
    {
        return $this->alwaysIncludeInContext;
    }

    public function setAlwaysIncludeInContext(bool $alwaysIncludeInContext): self
    {
        $this->alwaysIncludeInContext = $alwaysIncludeInContext;
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

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): self
    {
        $this->summary = $summary;
        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::LoreDocument;
    }
}
