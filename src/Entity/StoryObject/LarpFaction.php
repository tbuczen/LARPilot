<?php

namespace App\Entity\StoryObject;

use App\Entity\Enum\TargetType;
use App\Entity\Larp;
use App\Entity\Trait\CreatorAwareInterface;
use App\Repository\StoryObject\LarpFactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LarpFactionRepository::class)]
class LarpFaction extends StoryObject implements CreatorAwareInterface
{
    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'factions')]
    private Larp $larp;

    #[ORM\ManyToMany(targetEntity: LarpCharacter::class, mappedBy: 'factions')]
    private Collection $members;

    public function __construct()
    {
        parent::__construct();
        $this->members = new ArrayCollection();
    }

    /**
     * @return Collection<LarpCharacter>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(LarpCharacter $larpCharacter): self
    {
        if (!$this->members->contains($larpCharacter)) {
            $this->members[] = $larpCharacter;
            $larpCharacter->addFaction($this);
        }
        return $this;
    }

    public function removeMember(LarpCharacter $character): self
    {
        if ($this->members->removeElement($character)) {
            // set the owning side to null (unless already changed)
            $character->removeFaction($this);
        }
        return $this;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Faction;
    }

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): void
    {
        $this->larp = $larp;
    }
}
