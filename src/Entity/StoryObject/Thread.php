<?php

namespace App\Entity\StoryObject;

use App\Entity\Enum\TargetType;
use App\Entity\Larp;
use App\Entity\Tag;
use App\Repository\StoryObject\ThreadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ThreadRepository::class)]
class Thread extends StoryObject
{
    /** @var Collection<Quest> */
    #[ORM\OneToMany(targetEntity: Quest::class, mappedBy: 'thread')]
    private Collection $quests;

    /** @var Collection<Event> */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'thread')]
    private Collection $events;

    /** @var Collection<Character> Specifically needed involved characters */
    #[ORM\ManyToMany(targetEntity: Character::class, inversedBy: 'threads')]
    #[ORM\JoinTable(name: 'thread_involved_characters')]
    private Collection $involvedCharacters;

    /** @var Collection<Faction> Specifically needed involved factions */
    #[ORM\ManyToMany(targetEntity: Faction::class, inversedBy: 'threads')]
    #[ORM\JoinTable(name: 'thread_involved_factions')]
    private Collection $involvedFactions;

    /** @var Collection<Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'thread_tags')]
    private Collection $tags;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private ?array $decisionTree = null;

    public function __construct()
    {
        parent::__construct();
        $this->quests = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->involvedFactions = new ArrayCollection();
        $this->involvedCharacters = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getInvolvedCharacters(): Collection
    {
        return $this->involvedCharacters;
    }

    public function addInvolvedCharacter(Character $character): self
    {
        if (!$this->involvedCharacters->contains($character)) {
            $this->involvedCharacters->add($character);
        }
        return $this;
    }

    public function removeInvolvedCharacter(Character $character): self
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

    public function addInvolvedFaction(Faction $involvedFaction): self
    {
        if (!$this->involvedFactions->contains($involvedFaction)) {
            $this->involvedFactions->add($involvedFaction);
        }
        return $this;
    }

    public function removeInvolvedFaction(Faction $involvedFaction): self
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

    public function getDecisionTree(): ?array
    {
        return $this->decisionTree;
    }

    public function setDecisionTree(?array $decisionTree): self
    {
        $this->decisionTree = $decisionTree;
        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Thread;
    }
}
