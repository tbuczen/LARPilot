<?php

namespace App\Domain\EventPlanning\Service;

use App\Domain\EventPlanning\Entity\Enum\ConflictSeverity;
use App\Domain\EventPlanning\Entity\Enum\ConflictType;
use App\Domain\EventPlanning\Entity\PlanningResource;
use App\Domain\EventPlanning\Entity\ScheduledEvent;
use App\Domain\EventPlanning\Entity\ScheduledEventConflict;
use App\Domain\EventPlanning\Repository\ResourceBookingRepository;
use App\Domain\EventPlanning\Repository\ScheduledEventRepository;

readonly class ConflictDetectionService
{
    public function __construct(
        private ResourceBookingRepository $resourceBookingRepository,
        //        private ScheduledEventRepository  $scheduledEventRepository
    ) {
    }

    /**
     * Detect all conflicts for a scheduled event.
     *
     * @return ScheduledEventConflict[]
     */
    public function detectConflicts(ScheduledEvent $event): array
    {
        $conflicts = [];

        // For POC, only implement resource double-booking detection
        $conflicts = array_merge($conflicts, $this->detectResourceConflicts($event));

        return $conflicts;
    }

    /**
     * Detect resource double-booking conflicts.
     *
     * @return ScheduledEventConflict[]
     */
    private function detectResourceConflicts(ScheduledEvent $event): array
    {
        $conflicts = [];

        foreach ($event->getResourceBookings() as $booking) {
            $resource = $booking->getResource();

            // Skip if resource is shareable
            if ($resource->isShareable()) {
                continue;
            }

            // Find overlapping bookings for this resource
            $overlappingBookings = $this->resourceBookingRepository->findOverlappingBookings(
                $resource,
                $event->getEffectiveStartTime(),
                $event->getEffectiveEndTime(),
                $event
            );

            foreach ($overlappingBookings as $otherBooking) {
                $otherEvent = $otherBooking->getScheduledEvent();

                if ($otherEvent->getId() !== $event->getId()) {
                    $conflict = new ScheduledEventConflict();
                    $conflict->setEvent1($event);
                    $conflict->setEvent2($otherEvent);
                    $conflict->setType(ConflictType::RESOURCE_DOUBLE_BOOKING);
                    $conflict->setSeverity(
                        $booking->isRequired() ? ConflictSeverity::CRITICAL : ConflictSeverity::WARNING
                    );
                    $conflict->setDescription(
                        sprintf(
                            'Resource "%s" is already booked for "%s" from %s to %s',
                            $resource->getName(),
                            $otherEvent->getTitle(),
                            $otherEvent->getStartTime()->format('H:i'),
                            $otherEvent->getEndTime()->format('H:i')
                        )
                    );

                    $conflicts[] = $conflict;
                }
            }
        }

        return $conflicts;
    }

    /**
     * Check if a resource is available for a given time period.
     */
    public function isResourceAvailable(
        PlanningResource $resource,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?ScheduledEvent $excludeEvent = null
    ): bool {
        // Check resource availability window
        if (!$resource->isAvailableDuring($startTime, $endTime)) {
            return false;
        }

        // Check if resource is shareable
        if ($resource->isShareable()) {
            return true;
        }

        // Check for existing bookings
        $overlappingBookings = $this->resourceBookingRepository->findOverlappingBookings(
            $resource,
            $startTime,
            $endTime,
            $excludeEvent
        );

        return count($overlappingBookings) === 0;
    }

    /**
     * Get available quantity of a resource during a time period.
     */
    public function getAvailableQuantity(
        PlanningResource $resource,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?ScheduledEvent $excludeEvent = null
    ): int {
        if (!$resource->isAvailableDuring($startTime, $endTime)) {
            return 0;
        }

        $overlappingBookings = $this->resourceBookingRepository->findOverlappingBookings(
            $resource,
            $startTime,
            $endTime,
            $excludeEvent
        );

        $bookedQuantity = 0;
        foreach ($overlappingBookings as $booking) {
            $bookedQuantity += $booking->getQuantityNeeded();
        }

        return max(0, $resource->getQuantity() - $bookedQuantity);
    }
}
