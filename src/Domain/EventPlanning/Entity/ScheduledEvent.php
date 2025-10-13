<?php

namespace App\Domain\EventPlanning\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\CreatorAwareInterface;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\EventPlanning\Entity\Enum\EventStatus;
use App\Domain\EventPlanning\Repository\ScheduledEventRepository;
use App\Domain\Map\Entity\MapLocation;
use App\Domain\StoryObject\Entity\Event;
use App\Domain\StoryObject\Entity\Quest;
use App\Domain\StoryObject\Entity\Thread;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ScheduledEventRepository::class)]
class ScheduledEvent implements Timestampable, CreatorAwareInterface, \Stringable
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Larp $larp;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $startTime;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $endTime;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private int $setupMinutes = 0;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private int $cleanupMinutes = 0;

    #[ORM\Column(length: 50, enumType: EventStatus::class, options: ['default' => EventStatus::DRAFT])]
    private EventStatus $status = EventStatus::DRAFT;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $organizerNotes = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $visibleToPlayers = true;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: Quest::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Quest $quest = null;

    #[ORM\ManyToOne(targetEntity: Thread::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Thread $thread = null;

    #[ORM\ManyToOne(targetEntity: MapLocation::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?MapLocation $location = null;

    /** @var Collection<ResourceBooking> */
    #[ORM\OneToMany(targetEntity: ResourceBooking::class, mappedBy: 'scheduledEvent', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $resourceBookings;

    /** @var Collection<ScheduledEventConflict> */
    #[ORM\OneToMany(targetEntity: ScheduledEventConflict::class, mappedBy: 'event1', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $conflicts;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->resourceBookings = new ArrayCollection();
        $this->conflicts = new ArrayCollection();
    }

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): self
    {
        $this->larp = $larp;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getStartTime(): \DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): \DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getSetupMinutes(): int
    {
        return $this->setupMinutes;
    }

    public function setSetupMinutes(int $setupMinutes): self
    {
        $this->setupMinutes = $setupMinutes;
        return $this;
    }

    public function getCleanupMinutes(): int
    {
        return $this->cleanupMinutes;
    }

    public function setCleanupMinutes(int $cleanupMinutes): self
    {
        $this->cleanupMinutes = $cleanupMinutes;
        return $this;
    }

    public function getStatus(): EventStatus
    {
        return $this->status;
    }

    public function setStatus(EventStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getOrganizerNotes(): ?string
    {
        return $this->organizerNotes;
    }

    public function setOrganizerNotes(?string $organizerNotes): self
    {
        $this->organizerNotes = $organizerNotes;
        return $this;
    }

    public function isVisibleToPlayers(): bool
    {
        return $this->visibleToPlayers;
    }

    public function setVisibleToPlayers(bool $visibleToPlayers): self
    {
        $this->visibleToPlayers = $visibleToPlayers;
        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getQuest(): ?Quest
    {
        return $this->quest;
    }

    public function setQuest(?Quest $quest): self
    {
        $this->quest = $quest;
        return $this;
    }

    public function getThread(): ?Thread
    {
        return $this->thread;
    }

    public function setThread(?Thread $thread): self
    {
        $this->thread = $thread;
        return $this;
    }

    public function getLocation(): ?MapLocation
    {
        return $this->location;
    }

    public function setLocation(?MapLocation $location): self
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return Collection<ResourceBooking>
     */
    public function getResourceBookings(): Collection
    {
        return $this->resourceBookings;
    }

    public function addResourceBooking(ResourceBooking $booking): self
    {
        if (!$this->resourceBookings->contains($booking)) {
            $this->resourceBookings->add($booking);
            $booking->setScheduledEvent($this);
        }
        return $this;
    }

    public function removeResourceBooking(ResourceBooking $booking): self
    {
        if ($this->resourceBookings->removeElement($booking)) {
            if ($booking->getScheduledEvent() === $this) {
                $booking->setScheduledEvent(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<ScheduledEventConflict>
     */
    public function getConflicts(): Collection
    {
        return $this->conflicts;
    }

    public function addConflict(ScheduledEventConflict $conflict): self
    {
        if (!$this->conflicts->contains($conflict)) {
            $this->conflicts->add($conflict);
            $conflict->setEvent1($this);
        }
        return $this;
    }

    public function removeConflict(ScheduledEventConflict $conflict): self
    {
        if ($this->conflicts->removeElement($conflict)) {
            if ($conflict->getEvent1() === $this) {
                $conflict->setEvent1(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }

    /**
     * Get effective start time including setup
     */
    public function getEffectiveStartTime(): \DateTimeInterface
    {
        $effective = clone $this->startTime;
        if ($this->setupMinutes > 0) {
            $effective->modify('-' . $this->setupMinutes . ' minutes');
        }
        return $effective;
    }

    /**
     * Get effective end time including cleanup
     */
    public function getEffectiveEndTime(): \DateTimeInterface
    {
        $effective = clone $this->endTime;
        if ($this->cleanupMinutes > 0) {
            $effective->modify('+' . $this->cleanupMinutes . ' minutes');
        }
        return $effective;
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutes(): int
    {
        $diff = $this->startTime->diff($this->endTime);
        return ($diff->h * 60) + $diff->i;
    }

    /**
     * Check if there are any unresolved conflicts
     */
    public function hasUnresolvedConflicts(): bool
    {
        foreach ($this->conflicts as $conflict) {
            if (!$conflict->isResolved()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get story object reference if any linked
     */
    public function getStoryObjectReference(): ?string
    {
        if ($this->quest) {
            return 'Quest: ' . $this->quest->getTitle();
        }
        if ($this->thread) {
            return 'Thread: ' . $this->thread->getTitle();
        }
        if ($this->event) {
            return 'Event: ' . $this->event->getTitle();
        }
        return null;
    }
}
