<?php

namespace App\Entity\StoryObject;


use App\Entity\Larp;
use App\Repository\StoryObject\ThreadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\TargetType;

#[ORM\Entity(repositoryClass: ThreadRepository::class)]
class Thread extends StoryObject
{


    /** @var Collection<Quest> */
    #[ORM\OneToMany(targetEntity: Quest::class, mappedBy: 'thread')]
    private Collection $quests;

    /** @var Collection<Event> */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'thread')]
    private Collection $events;

    /** @var Collection<LarpCharacter> Specifically needed involved characters */
    #[ORM\ManyToMany(targetEntity: LarpCharacter::class, mappedBy: 'threads')]
    private Collection $involvedCharacters;

    /** @var Collection<LarpFaction> Specifically needed involved factions */
    #[ORM\ManyToMany(targetEntity: LarpFaction::class, mappedBy: 'threads')]
    private Collection $involvedFactions;

    public function __construct()
    {
        parent::__construct();
        $this->quests = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->involvedFactions = new ArrayCollection();
        $this->involvedCharacters = new ArrayCollection();
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


    public function getQuests(): Collection
    {
        return $this->quests;
    }

    public function setQuests(Collection $quests): void
    {
        $this->quests = $quests;
    }

    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function setEvents(Collection $events): void
    {
        $this->events = $events;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Thread;
    }
}