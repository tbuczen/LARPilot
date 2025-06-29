<?php

namespace App\Entity;

use App\Entity\Enum\SubmissionStatus;
use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\LarpApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: LarpApplicationRepository::class)]
#[ORM\Index(columns: ['larp_id'])]
#[ORM\Index(columns: ['user_id'])]
#[ORM\Index(columns: ['preferred_tags_id'])]
#[ORM\Index(columns: ['unwanted_tags_id'])]
class LarpApplication implements Timestampable, CreatorAwareInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    public function __construct()
    {
        $this->id = \Symfony\Component\Uid\Uuid::v4();
        $this->choices = new ArrayCollection();
    }

    #[ORM\Column(length: 50)]
    private ?SubmissionStatus $status = null;

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

    /** @var Collection<Tag> */
    #[ORM\ManyToOne(targetEntity: Tag::class)]
    private Collection $preferredTags;

    /** @var Collection<Tag> */
    #[ORM\ManyToOne(targetEntity: Tag::class)]
    private Collection $unwantedTags;

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
        if ($this->choices->removeElement($choice)) {
            // orphanRemoval will handle deletion
        }

        return $this;
    }
}
