<?php

namespace App\Entity\StoryObject;


use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Repository\StoryObject\EventRepository;
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

    public function setInvolvedCharacters(Collection $involvedCharacters): void
    {
        $this->involvedCharacters = $involvedCharacters;
    }

    public function getInvolvedFactions(): Collection
    {
        return $this->involvedFactions;
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

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Event;
    }
}