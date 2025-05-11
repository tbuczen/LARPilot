<?php

namespace App\Entity\StoryObject;


use App\Entity\Larp;
use App\Repository\StoryObject\QuestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\TargetType;

#[ORM\Entity(repositoryClass: QuestRepository::class)]

class Quest extends StoryObject
{

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    protected ?Larp $larp;

    #[ORM\ManyToOne(targetEntity: Thread::class, inversedBy: 'quests')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Thread $thread = null;

    /** @var Collection<LarpCharacter> Specifically needed involved characters */
    #[ORM\ManyToMany(targetEntity: LarpCharacter::class, mappedBy: 'quests')]
    private Collection $involvedCharacters;

    /** @var Collection<LarpFaction> Specifically needed involved factions */
    #[ORM\ManyToMany(targetEntity: LarpFaction::class, mappedBy: 'quests')]
    private Collection $involvedFactions;

    public function __construct()
    {
        parent::__construct();
        $this->involvedFactions = new ArrayCollection();
        $this->involvedCharacters = new ArrayCollection();
    }

    public function getThread(): ?Thread
    {
        return $this->thread;
    }

    public function setThread(?Thread $thread): void
    {
        $this->thread = $thread;
    }

    public function getInvolvedCharacters(): Collection
    {
        return $this->involvedCharacters;
    }

    public function addInvolvedCharacter(LarpCharacter $character): self
    {
        if (!$this->involvedCharacters->contains($character)) {
            $this->involvedCharacters->add($character);
        }
        return $this;
    }

    public function removeInvolvedCharacter(LarpCharacter $character): self
    {
        if ($this->involvedCharacters->contains($character)) {
            $this->involvedCharacters->remove($character);
        }
        return $this;
    }

    public function setInvolvedCharacters(Collection $involvedCharacters): void
    {
        $this->involvedCharacters = $involvedCharacters;
    }

    public function getInvolvedFactions(): Collection
    {
        return $this->involvedFactions;
    }

    public function addInvolvedFaction(LarpFaction $involvedFaction): self
    {
        if (!$this->involvedFactions->contains($involvedFaction)) {
            $this->involvedFactions->add($involvedFaction);
        }
        return $this;
    }

    public function removeInvolvedFaction(LarpFaction $involvedFaction): self
    {
        if ($this->involvedFactions->contains($involvedFaction)) {
            $this->involvedFactions->remove($involvedFaction);
        }
        return $this;
    }

    public function setInvolvedFactions(Collection $involvedFactions): void
    {
        $this->involvedFactions = $involvedFactions;
    }

    public function setLarp(?Larp $larp): void
    {
        $this->larp = $larp;
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Quest;
    }


}