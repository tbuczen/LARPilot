<?php

namespace App\Domain\EventPlanning\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\EventPlanning\Entity\Enum\EventStatus;
use App\Domain\EventPlanning\Entity\ScheduledEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service for formatting ScheduledEvent entities for FullCalendar display
 */
class CalendarEventFormatter
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Format a scheduled event for FullCalendar
     *
     * @param ScheduledEvent $event The event to format
     * @param Larp $larp The LARP context (for URL generation)
     * @param string $urlRoute The route to use for the event URL (defaults to view page)
     * @return array<string, mixed> The formatted event data
     */
    public function formatEvent(ScheduledEvent $event, Larp $larp, string $urlRoute = 'backoffice_event_planner_event_view'): array
    {
        return [
            'id' => $event->getId()->toRfc4122(),
            'title' => $event->getTitle(),
            'start' => $event->getStartTime()->format('c'),
            'end' => $event->getEndTime()->format('c'),
            'backgroundColor' => $this->getEventColor($event),
            'borderColor' => $this->getEventBorderColor($event),
            'url' => $this->urlGenerator->generate($urlRoute, [
                'larp' => $larp->getId(),
                'event' => $event->getId(),
            ]),
            'extendedProps' => [
                'status' => $event->getStatus()->value,
                'location' => $event->getLocation()?->getName(),
                'hasConflicts' => $event->hasUnresolvedConflicts(),
                'description' => $event->getDescription(),
            ],
        ];
    }

    /**
     * Get the background color for an event based on its status and conflicts
     */
    private function getEventColor(ScheduledEvent $event): string
    {
        if ($event->hasUnresolvedConflicts()) {
            return '#dc3545'; // Red for conflicts
        }

        return match ($event->getStatus()) {
            EventStatus::DRAFT => '#6c757d',
            EventStatus::CONFIRMED => '#28a745',
            EventStatus::IN_PROGRESS => '#007bff',
            EventStatus::COMPLETED => '#17a2b8',
            EventStatus::CANCELLED => '#6c757d',
        };
    }

    /**
     * Get the border color for an event
     */
    private function getEventBorderColor(ScheduledEvent $event): string
    {
        if ($event->hasUnresolvedConflicts()) {
            return '#bd2130';
        }

        return $this->getEventColor($event);
    }
}
