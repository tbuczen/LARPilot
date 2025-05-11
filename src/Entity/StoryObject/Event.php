<?php

namespace App\Entity\StoryObject;


use App\Entity\Enum\StoryTimeUnit;
use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Repository\StoryObject\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\TargetType;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event extends StoryObject
{

    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    protected Larp $larp;

    /** @var Collection<LarpParticipant> Participants (technical) needed for event to happen */
    #[ORM\ManyToMany(targetEntity: LarpParticipant::class)]
    private Collection $techParticipants;

    /** @var Collection<LarpCharacter> Specifically needed involved characters */
    #[ORM\ManyToMany(targetEntity: LarpCharacter::class)]
    private Collection $involvedCharacters;

    /** @var Collection<LarpFaction> Specifically needed involved factions */
    #[ORM\ManyToMany(targetEntity: LarpFaction::class)]
    private Collection $involvedFactions;

    #[ORM\ManyToOne(targetEntity: Thread::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Thread $thread = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $storyMoment = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $storyTime = null; // np. 0 = start LARPa

    #[ORM\Column(length: 20, nullable: true, enumType: StoryTimeUnit::class)]
    private ?StoryTimeUnit $storyTimeUnit = null;

    public function __construct()
    {
        parent::__construct();
        $this->techParticipants = new ArrayCollection();
        $this->involvedCharacters = new ArrayCollection();
        $this->involvedFactions = new ArrayCollection();
    }

    public function getTechParticipants(): Collection
    {
        return $this->techParticipants;
    }

    public function setTechParticipants(Collection $techParticipants): void
    {
        $this->techParticipants = $techParticipants;
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

    public function getThread(): ?Thread
    {
        return $this->thread;
    }

    public function setThread(?Thread $thread): void
    {
        $this->thread = $thread;
    }

    public function getStoryMoment(): ?string
    {
        return $this->storyMoment;
    }

    public function setStoryMoment(?string $storyMoment): void
    {
        $this->storyMoment = $storyMoment;
    }

    public function getStoryTime(): ?int
    {
        return $this->storyTime;
    }

    public function setStoryTime(?int $storyTime): void
    {
        $this->storyTime = $storyTime;
    }

    public function getStoryTimeUnit(): ?StoryTimeUnit
    {
        return $this->storyTimeUnit;
    }

    public function setStoryTimeUnit(?StoryTimeUnit $storyTimeUnit): void
    {
        $this->storyTimeUnit = $storyTimeUnit;
    }

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): void
    {
        $this->larp = $larp;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Event;
    }
}