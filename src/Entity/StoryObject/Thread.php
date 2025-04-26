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

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    protected ?Larp $larp;

    /** @var Collection<Quest> */
    #[ORM\OneToMany(targetEntity: Quest::class, mappedBy: 'thread')]
    private Collection $quests;

    /** @var Collection<Event> */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'thread')]
    private Collection $events;

    public function __construct()
    {
        parent::__construct();
        $this->quests = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
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