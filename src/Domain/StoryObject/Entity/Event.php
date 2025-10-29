<?php

namespace App\Domain\StoryObject\Entity;

use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Entity\Tag;
use App\Domain\StoryObject\Entity\Enum\EventCategory;
use App\Domain\StoryObject\Entity\Enum\StoryTimeUnit;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event extends StoryObject
{
    /** @var Collection<LarpParticipant> Participants (technical) needed for event to happen */
    #[ORM\ManyToMany(targetEntity: LarpParticipant::class)]
    private Collection $techParticipants;

    /** @var Collection<Character> Specifically needed involved characters */
    #[ORM\ManyToMany(targetEntity: Character::class)]
    private Collection $involvedCharacters;

    /** @var Collection<Faction> Specifically needed involved factions */
    #[ORM\ManyToMany(targetEntity: Faction::class)]
    private Collection $involvedFactions;

    /** @var Collection<Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'event_tags')]
    private Collection $tags;

    #[ORM\ManyToOne(targetEntity: Thread::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Thread $thread = null;

    #[ORM\ManyToOne(targetEntity: Place::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Place $place = null;

    #[Gedmo\Versioned]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $storyMoment = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $storyTime = null; // np. 0 = start LARPa

    #[ORM\Column(length: 20, nullable: true, enumType: StoryTimeUnit::class)]
    private ?StoryTimeUnit $storyTimeUnit = null;

    #[Gedmo\Versioned]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startTime = null;

    #[Gedmo\Versioned]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endTime = null;

    #[Gedmo\Versioned]
    #[ORM\Column(length: 20, nullable: false, enumType: EventCategory::class)]
    private EventCategory $category = EventCategory::Current;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $knownPublicly = false;

    public function __construct()
    {
        parent::__construct();
        $this->techParticipants = new ArrayCollection();
        $this->involvedCharacters = new ArrayCollection();
        $this->involvedFactions = new ArrayCollection();
        $this->tags = new ArrayCollection();
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

    public function getThread(): ?Thread
    {
        return $this->thread;
    }

    public function setThread(?Thread $thread): void
    {
        $this->thread = $thread;
    }

    public function getPlace(): ?Place
    {
        return $this->place;
    }

    public function setPlace(?Place $place): void
    {
        $this->place = $place;
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

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): void
    {
        $this->endTime = $endTime;
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


    public function getCategory(): EventCategory
    {
        return $this->category;
    }

    public function setCategory(EventCategory $category): void
    {
        $this->category = $category;
    }

    public function isKnownPublicly(): bool
    {
        return $this->knownPublicly;
    }

    public function setKnownPublicly(bool $knownPublicly): void
    {
        $this->knownPublicly = $knownPublicly;
    }

    /**
     * Check if this event is public (visible to everyone).
     * An event is public if:
     * - It's marked as knownPublicly, OR
     * - It has no specific involved characters or factions
     */
    public function isPublic(): bool
    {
        return $this->knownPublicly || ($this->involvedCharacters->isEmpty() && $this->involvedFactions->isEmpty());
    }

    /**
     * Check if this event is visible to a specific character.
     * An event is visible to a character if:
     * - It's marked as knownPublicly, OR
     * - It's public (no involved characters/factions), OR
     * - The character is in the involvedCharacters list, OR
     * - The character belongs to one of the involvedFactions
     */
    public function isVisibleToCharacter(Character $character): bool
    {
        if ($this->knownPublicly || $this->isPublic()) {
            return true;
        }

        if ($this->involvedCharacters->contains($character)) {
            return true;
        }

        foreach ($this->involvedFactions as $faction) {
            if ($character->getFactions()->contains($faction)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this event is visible to members of a specific faction.
     * An event is visible to faction members if:
     * - It's marked as knownPublicly, OR
     * - It's public (no involved characters/factions), OR
     * - The faction is in the involvedFactions list
     */
    public function isVisibleToFaction(Faction $faction): bool
    {
        if ($this->knownPublicly || $this->isPublic()) {
            return true;
        }

        return $this->involvedFactions->contains($faction);
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Event;
    }
}
