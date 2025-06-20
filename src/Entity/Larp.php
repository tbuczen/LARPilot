<?php

namespace App\Entity;

use App\Entity\Enum\LarpStageStatus;
use App\Entity\LarpApplication;
use App\Entity\StoryObject\Event;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\LarpRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LarpRepository::class)]
class Larp implements Timestampable, CreatorAwareInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column(length: 255)]
    private ?LarpStageStatus $status = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $maxCharacterChoices = 1;

    /** @var Collection<LarpCharacter> */
    #[ORM\OneToMany(targetEntity: LarpCharacter::class, mappedBy: 'larp')]
    private Collection $characters;

    /** @var Collection<LarpApplication> */
    #[ORM\OneToMany(targetEntity: LarpApplication::class, mappedBy: 'larp')]
    private Collection $applications;

    /** @var Collection<LarpParticipant> */
    #[ORM\OneToMany(targetEntity: LarpParticipant::class, mappedBy: 'larp')]
    private Collection $larpParticipants;

    /** @var Collection<Event> */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'larp')]
    private Collection $events;
    /** @var Collection<Skill> */
    #[ORM\OneToMany(targetEntity: Skill::class, mappedBy: 'larp')]
    private Collection $skills;

    /** @var Collection<LarpIntegration> */
    #[ORM\OneToMany(targetEntity: LarpIntegration::class, mappedBy: 'larp')]
    private Collection $integrations;

    /** @var Collection<LarpFaction> */
    #[ORM\OneToMany(targetEntity: LarpFaction::class, mappedBy: 'larp')]
    private Collection $factions;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->characters = new ArrayCollection();
        $this->applications = new ArrayCollection();
        $this->larpParticipants = new ArrayCollection();
        $this->factions = new ArrayCollection();
        $this->integrations = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getStatus(): ?LarpStageStatus
    {
        return $this->status;
    }

    public function setStatus(LarpStageStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getMaxCharacterChoices(): int
    {
        return $this->maxCharacterChoices;
    }

    public function setMaxCharacterChoices(int $maxCharacterChoices): static
    {
        $this->maxCharacterChoices = $maxCharacterChoices;

        return $this;
    }

    /**
     * @return Collection<int, LarpCharacter>
     */
    public function getCharacters(): Collection
    {
        return $this->characters;
    }

    public function addCharacter(LarpCharacter $character): static
    {
        if (!$this->characters->contains($character)) {
            $this->characters->add($character);
            $character->setLarp($this);
        }

        return $this;
    }

    public function removeCharacter(LarpCharacter $character): static
    {
        if ($this->characters->removeElement($character)) {
            // set the owning side to null (unless already changed)
            if ($character->getLarp() === $this) {
                $character->setLarp(null);
            }
        }

        return $this;
    }

    // Add appropriate getter and helper methods:
    public function getFactions(): Collection
    {
        return $this->factions;
    }

    public function addFaction(LarpFaction $element): self
    {
        if (!$this->factions->contains($element)) {
            $this->factions[] = $element;
            $element->setLarp($this);
        }
        return $this;
    }

    public function removeFaction(LarpFaction $element): self
    {
        if ($this->factions->removeElement($element)) {
            $element->setLarp(null);
        }
        return $this;
    }

    public function addParticipant(LarpParticipant $element): self
    {
        if (!$this->larpParticipants->contains($element)) {
            $this->larpParticipants[] = $element;
            $element->setLarp($this);
        }
        return $this;
    }

    public function removeParticipant(LarpParticipant $element): self
    {
        if ($this->larpParticipants->removeElement($element)) {
            $element->setLarp(null);
        }
        return $this;
    }

    public function getParticipants(): Collection
    {
        return $this->larpParticipants;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
