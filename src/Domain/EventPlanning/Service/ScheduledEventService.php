<?php

namespace App\Domain\EventPlanning\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\EventPlanning\Entity\Enum\EventStatus;
use App\Domain\EventPlanning\Entity\ScheduledEvent;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for managing scheduled events
 */
class ScheduledEventService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ConflictDetectionService $conflictDetection,
    ) {
    }

    /**
     * Create a new scheduled event with minimal data
     *
     * @param Larp $larp The LARP this event belongs to
     * @param \DateTimeImmutable $startTime Event start time
     * @param \DateTimeImmutable $endTime Event end time
     * @param string|null $title Optional title (defaults to "New Event")
     * @return ScheduledEvent The created event
     */
    public function createEvent(
        Larp $larp,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        ?string $title = null
    ): ScheduledEvent {
        $event = new ScheduledEvent();
        $event->setLarp($larp);
        $event->setTitle($title ?? 'New Event');
        $event->setStartTime($startTime);
        $event->setEndTime($endTime);
        $event->setStatus(EventStatus::DRAFT);
        $event->setVisibleToPlayers(false);

        $this->em->persist($event);
        $this->em->flush();

        // Check for conflicts after creation
        $this->conflictDetection->detectConflicts($event);

        return $event;
    }

    /**
     * Update event times (for drag-and-drop rescheduling)
     *
     * @param ScheduledEvent $event The event to update
     * @param \DateTimeImmutable $startTime New start time
     * @param \DateTimeImmutable $endTime New end time
     * @return ScheduledEvent The updated event
     */
    public function updateEventTimes(
        ScheduledEvent $event,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime
    ): ScheduledEvent {
        $event->setStartTime($startTime);
        $event->setEndTime($endTime);

        $this->em->flush();

        // Re-check conflicts after time change
        $this->conflictDetection->detectConflicts($event);

        return $event;
    }
}
