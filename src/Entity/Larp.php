<?php

namespace App\Entity;

use App\Entity\Trait\UuidTraitEntity;
use App\Repository\LarpRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;

#[ORM\Entity(repositoryClass: LarpRepository::class)]
class Larp implements Timestampable
{
    use UuidTraitEntity;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    /** @var Collection<LarpCharacter>  */
    #[ORM\OneToMany(targetEntity: LarpCharacter::class, mappedBy: 'larp')]
    private Collection $characters;

    /** @var Collection<LarpCharacterSubmission>  */
    #[ORM\OneToMany(targetEntity: LarpCharacterSubmission::class, mappedBy: 'larp')]
    private Collection $submissions;

    /** @var Collection<LarpParticipant>  */
    #[ORM\OneToMany(targetEntity: LarpParticipant::class, mappedBy: 'larp')]
    private Collection $larpParticipants;

    #[ORM\ManyToMany(targetEntity: LarpFaction::class, mappedBy: 'larps')]
    private Collection $factions;

    public function __construct()
    {
        $this->characters = new ArrayCollection();
        $this->submissions = new ArrayCollection();
        $this->larpParticipants = new ArrayCollection();
        $this->factions = new ArrayCollection();
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function addFaction(LarpFaction $faction): self
    {
        if (!$this->factions->contains($faction)) {
            $this->factions[] = $faction;
            $faction->addLarp($this);
        }
        return $this;
    }

    public function removeFaction(LarpFaction $faction): self
    {
        if ($this->factions->removeElement($faction)) {
            $faction->removeLarp($this);
        }
        return $this;
    }
}
