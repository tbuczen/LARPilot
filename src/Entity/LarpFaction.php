<?php

namespace App\Entity;

use App\Entity\Trait\UuidTraitEntity;
use App\Repository\LarpFactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LarpFactionRepository::class)]
class LarpFaction
{
    use UuidTraitEntity;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // A faction can be associated with multiple larps:
    #[ORM\ManyToMany(targetEntity: Larp::class, inversedBy: 'factions')]
    private Collection $larps;

    // A faction can have many participants:
    #[ORM\OneToMany(targetEntity: LarpParticipant::class, mappedBy: 'faction')]
    private Collection $members;

    public function __construct()
    {
        $this->larps = new ArrayCollection();
        $this->members = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    /**
     * @return Collection<Larp>
     */
    public function getLarps(): Collection
    {
        return $this->larps;
    }

    public function addLarp(Larp $larp): self
    {
        if (!$this->larps->contains($larp)) {
            $this->larps[] = $larp;
        }
        return $this;
    }

    public function removeLarp(Larp $larp): self
    {
        $this->larps->removeElement($larp);
        return $this;
    }

    /**
     * @return Collection<LarpParticipant>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addParticipant(LarpParticipant $participant): self
    {
        if (!$this->members->contains($participant)) {
            $this->members[] = $participant;
            $participant->setFaction($this);
        }
        return $this;
    }

    public function removeParticipant(LarpParticipant $participant): self
    {
        if ($this->members->removeElement($participant)) {
            // set the owning side to null (unless already changed)
            if ($participant->getFaction() === $this) {
                $participant->setFaction(null);
            }
        }
        return $this;
    }
}
