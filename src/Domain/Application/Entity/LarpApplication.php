<?php

namespace App\Domain\Application\Entity;

use App\Domain\Account\Entity\User;
use App\Domain\Application\Entity\Enum\SubmissionStatus;
use App\Domain\Application\Repository\LarpApplicationRepository;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Entity\Trait\CreatorAwareInterface;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LarpApplicationRepository::class)]
#[ORM\Index(columns: ['larp_id'])]
#[ORM\Index(columns: ['user_id'])]
class LarpApplication implements Timestampable, CreatorAwareInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    /** @var Collection<Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'larp_application_preferred_tags')]
    private Collection $preferredTags;

    /** @var Collection<Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'larp_application_unwanted_tags')]
    private Collection $unwantedTags;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->choices = new ArrayCollection();
        $this->preferredTags = new ArrayCollection();
        $this->unwantedTags = new ArrayCollection();
    }

    #[ORM\Column(length: 50)]
    private ?SubmissionStatus $status = SubmissionStatus::NEW;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $favouriteStyle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $triggers = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /** @var Collection<LarpApplicationChoice> */
    #[ORM\OneToMany(targetEntity: LarpApplicationChoice::class, mappedBy: 'application', cascade: ['persist'], orphanRemoval: true)]
    private Collection $choices;

    // Add these methods for tag management
    public function getPreferredTags(): Collection
    {
        return $this->preferredTags;
    }

    public function addPreferredTag(Tag $tag): static
    {
        if (!$this->preferredTags->contains($tag)) {
            $this->preferredTags->add($tag);
        }
        return $this;
    }

    public function removePreferredTag(Tag $tag): static
    {
        $this->preferredTags->removeElement($tag);
        return $this;
    }

    public function getUnwantedTags(): Collection
    {
        return $this->unwantedTags;
    }

    public function addUnwantedTag(Tag $tag): static
    {
        if (!$this->unwantedTags->contains($tag)) {
            $this->unwantedTags->add($tag);
        }
        return $this;
    }

    public function removeUnwantedTag(Tag $tag): static
    {
        $this->unwantedTags->removeElement($tag);
        return $this;
    }

    public function getStatus(): ?SubmissionStatus
    {
        return $this->status;
    }

    public function setStatus(SubmissionStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getFavouriteStyle(): ?string
    {
        return $this->favouriteStyle;
    }

    public function setFavouriteStyle(?string $favouriteStyle): static
    {
        $this->favouriteStyle = $favouriteStyle;

        return $this;
    }

    public function getTriggers(): ?string
    {
        return $this->triggers;
    }

    public function setTriggers(?string $triggers): static
    {
        $this->triggers = $triggers;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail ?? $this->getUser()->getContactEmail();
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(?Larp $larp): void
    {
        $this->larp = $larp;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return Collection<int, LarpApplicationChoice>
     */
    public function getChoices(): Collection
    {
        return $this->choices;
    }

    public function addChoice(LarpApplicationChoice $choice): static
    {
        if (!$this->choices->contains($choice)) {
            $this->choices->add($choice);
            $choice->setApplication($this);
        }

        return $this;
    }

    public function removeChoice(LarpApplicationChoice $choice): static
    {
        $this->choices->removeElement($choice);
        return $this;
    }
}
