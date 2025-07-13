<?php

namespace App\Entity\StoryObject;

use App\Entity\Enum\TargetType;
use App\Entity\Larp;
use App\Entity\Tag;
use App\Repository\StoryObject\QuestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestRepository::class)]

class Quest extends StoryObject
{
    #[ORM\ManyToOne(targetEntity: Thread::class, inversedBy: 'quests')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Thread $thread = null;

    /** @var Collection<LarpCharacter> Specifically needed involved characters */
    #[ORM\ManyToMany(targetEntity: LarpCharacter::class)]
    #[ORM\JoinTable(name: 'quest_involved_characters')]
    private Collection $involvedCharacters;

    /** @var Collection<LarpFaction> Specifically needed involved factions */
    #[ORM\ManyToMany(targetEntity: LarpFaction::class)]
    #[ORM\JoinTable(name: 'quest_involved_factions')]
    private Collection $involvedFactions;

    /** @var Collection<Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'quest_tags')]
    private Collection $tags;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private ?array $decisionTree = null;

    public function __construct()
    {
        parent::__construct();
        $this->involvedFactions = new ArrayCollection();
        $this->involvedCharacters = new ArrayCollection();
        $this->tags = new ArrayCollection();
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
        return TargetType::Quest;
    }
}