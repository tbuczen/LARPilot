<?php

namespace App\Domain\StoryObject\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\CreatorAwareInterface;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\LarpAwareInterface;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\StoryObject\Repository\KnowledgeDocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: KnowledgeDocumentRepository::class)]
#[ORM\Index(columns: ['title'])]
#[ORM\Index(columns: ['larp_id'])]
#[ORM\Table(name: 'knowledge_document')]
class KnowledgeDocument implements CreatorAwareInterface, Timestampable, LarpAwareInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    /**
     * LARP context - all documents belong to a LARP
     */
    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isPublic = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    /**
     * Story objects that own this document
     * Can be Characters, Factions, or any other StoryObject type
     *
     * @var Collection<StoryObject>
     */
    #[ORM\ManyToMany(targetEntity: StoryObject::class)]
    #[ORM\JoinTable(name: 'knowledge_document_owner')]
    private Collection $owners;

    /**
     * Story objects that can view this document
     * For Characters: direct access
     * For Factions: all members gain access
     *
     * @var Collection<StoryObject>
     */
    #[ORM\ManyToMany(targetEntity: StoryObject::class)]
    #[ORM\JoinTable(name: 'knowledge_document_visible_to')]
    private Collection $visibleTo;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->owners = new ArrayCollection();
        $this->visibleTo = new ArrayCollection();
    }

    // ========================================================================
    // Basic Getters/Setters
    // ========================================================================

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
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

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    // ========================================================================
    // Owner Management
    // ========================================================================

    /**
     * @return Collection<StoryObject>
     */
    public function getOwners(): Collection
    {
        return $this->owners;
    }

    public function addOwner(StoryObject $owner): self
    {
        if (!$this->owners->contains($owner)) {
            $this->owners->add($owner);
        }
        return $this;
    }

    public function removeOwner(StoryObject $owner): self
    {
        $this->owners->removeElement($owner);
        return $this;
    }

    public function isOwnedBy(StoryObject $storyObject): bool
    {
        return $this->owners->contains($storyObject);
    }

    // ========================================================================
    // Visibility Control Methods
    // ========================================================================

    /**
     * @return Collection<StoryObject>
     */
    public function getVisibleTo(): Collection
    {
        return $this->visibleTo;
    }

    public function addVisibleTo(StoryObject $storyObject): self
    {
        if (!$this->visibleTo->contains($storyObject)) {
            $this->visibleTo->add($storyObject);
        }
        return $this;
    }

    public function removeVisibleTo(StoryObject $storyObject): self
    {
        $this->visibleTo->removeElement($storyObject);
        return $this;
    }

    // ========================================================================
    // Visibility Logic
    // ========================================================================

    /**
     * Check if a character can access this document
     *
     * Visibility rules:
     * 1. Public documents are visible to all
     * 2. Character owners can see their documents
     * 3. Faction members can see faction-owned documents
     * 4. Explicit visibility grants (characters or factions)
     */
    public function isVisibleToCharacter(Character $character): bool
    {
        // 1. Public documents
        if ($this->isPublic) {
            return true;
        }

        // 2. Owner-based visibility
        foreach ($this->owners as $owner) {
            // Direct character ownership
            if ($owner instanceof Character && $owner->getId()->equals($character->getId())) {
                return true;
            }

            // Faction ownership - check if character is a member
            if ($owner instanceof Faction && $owner->getMembers()->contains($character)) {
                return true;
            }
        }

        // 3. Explicit visibility grants
        foreach ($this->visibleTo as $storyObject) {
            // Direct character access
            if ($storyObject instanceof Character && $storyObject->getId()->equals($character->getId())) {
                return true;
            }

            // Faction-based access - check if character is a member
            if ($storyObject instanceof Faction && $storyObject->getMembers()->contains($character)) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}
