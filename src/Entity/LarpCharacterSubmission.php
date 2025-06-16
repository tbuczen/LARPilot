<?php

namespace App\Entity;

use App\Entity\Enum\SubmissionStatus;
use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\LarpCharacterSubmissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: LarpCharacterSubmissionRepository::class)]
class LarpCharacterSubmission implements Timestampable, CreatorAwareInterface
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

    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /** @var Collection<LarpCharacterSubmissionChoice> */
    #[ORM\OneToMany(targetEntity: LarpCharacterSubmissionChoice::class, mappedBy: 'submission', cascade: ['persist'], orphanRemoval: true)]
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
     * @return Collection<int, LarpCharacterSubmissionChoice>
     */
    public function getChoices(): Collection
    {
        return $this->choices;
    }

    public function addChoice(LarpCharacterSubmissionChoice $choice): static
    {
        if (!$this->choices->contains($choice)) {
            $this->choices->add($choice);
            $choice->setSubmission($this);
        }

        return $this;
    }

    public function removeChoice(LarpCharacterSubmissionChoice $choice): static
    {
        if ($this->choices->removeElement($choice)) {
            // orphanRemoval will handle deletion
        }

        return $this;
    }
}
