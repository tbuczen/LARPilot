<?php

namespace App\Entity\StoryObject;

use App\Entity\Enum\TargetType;
use App\Entity\Larp;
use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\StoryObject\LarpFactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LarpFactionRepository::class)]
class LarpFaction extends StoryObject implements CreatorAwareInterface
{
    #[ORM\ManyToMany(targetEntity: Larp::class, inversedBy: 'factions')]
    private Collection $larps;

    #[ORM\ManyToMany(targetEntity: LarpCharacter::class, mappedBy: 'factions')]
    private Collection $members;

    public function __construct()
    {
        parent::__construct();
        $this->larps = new ArrayCollection();
        $this->members = new ArrayCollection();
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

    public function getLarp(): ?Larp
    {
        // TODO: Implement getLarp() method.
    }
}
