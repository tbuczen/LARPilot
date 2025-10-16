<?php

namespace App\Domain\EventPlanning\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\EventPlanning\Entity\Enum\EventStatus;
use App\Domain\EventPlanning\Entity\ScheduledEvent;
use App\Domain\EventPlanning\Form\Filter\ScheduledEventFilterType;
use App\Domain\EventPlanning\Repository\PlanningResourceRepository;
use App\Domain\EventPlanning\Repository\ScheduledEventRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/event-planner/calendar', name: 'backoffice_event_planner_calendar_')]
class CalendarController extends BaseController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, Larp $larp): Response
    {
        // Calculate default calendar view: week of LARP
        $defaultStart = $larp->getStartDate() ?? new \DateTime();
        $defaultEnd = $larp->getEndDate() ?? (clone $defaultStart)->modify('+7 days');

        // Create filter form
        $filterForm = $this->createForm(ScheduledEventFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        return $this->render('backoffice/event_planner/calendar/index.html.twig', [
            'larp' => $larp,
            'defaultStart' => $defaultStart,
            'defaultEnd' => $defaultEnd,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/events', name: 'events_api', methods: ['GET'])]
    public function eventsApi(
        Request $request,
        Larp $larp,
        ScheduledEventRepository $eventRepository
    ): JsonResponse {
        $startStr = $request->query->get('start');
        $endStr = $request->query->get('end');

        // Simple approach: just take the date/time part, ignore timezone
        $start = new \DateTime(substr($startStr, 0, 19)); // 2025-09-21T00:00:00
        $end = new \DateTime(substr($endStr, 0, 19));

        // Build base query
        $qb = $eventRepository->createQueryBuilder('se')
            ->where('se.larp = :larp')
            ->andWhere('se.startTime <= :end')
            ->andWhere('se.endTime >= :start')
            ->setParameter('larp', $larp)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        // Apply filters from query parameters
        if ($title = $request->query->get('title')) {
            $qb->andWhere('se.title LIKE :title')
                ->setParameter('title', '%' . $title . '%');
        }

        if ($status = $request->query->get('status')) {
            $qb->andWhere('se.status = :status')
                ->setParameter('status', $status);
        }

        if ($locationId = $request->query->get('location')) {
            $qb->andWhere('se.location = :location')
                ->setParameter('location', $locationId);
        }

        if ($resourceId = $request->query->get('resource')) {
            $qb->join('se.resourceBookings', 'rb')
                ->join('rb.resource', 'r')
                ->andWhere('r.id = :resource')
                ->setParameter('resource', $resourceId);
        }

        if ($characterId = $request->query->get('character')) {
            $qb->join('se.resourceBookings', 'rb2')
                ->join('rb2.resource', 'r2')
                ->andWhere('r2.character = :character')
                ->setParameter('character', $characterId);
        }

        $events = $qb->getQuery()->getResult();

        $calendarEvents = [];
        foreach ($events as $event) {
            $calendarEvents[] = [
                'id' => $event->getId()->toRfc4122(),
                'title' => $event->getTitle(),
                'start' => $event->getStartTime()->format('c'),
                'end' => $event->getEndTime()->format('c'),
                'backgroundColor' => $this->getEventColor($event),
                'borderColor' => $this->getEventBorderColor($event),
                'url' => $this->generateUrl('backoffice_event_planner_event_view', [
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

        return $this->json($calendarEvents);
    }

    #[Route('/resources', name: 'resources_api', methods: ['GET'])]
    public function resourcesApi(
        Request $request,
        Larp $larp,
        PlanningResourceRepository $resourceRepository
    ): JsonResponse {
        $startStr = $request->query->get('start');
        $endStr = $request->query->get('end');

        // Simple approach: just take the date/time part, ignore timezone
        $start = new \DateTime(substr($startStr, 0, 19)); // 2025-09-21T00:00:00
        $end = new \DateTime(substr($endStr, 0, 19));

        $resources = $resourceRepository->findAvailableDuring($larp, $start, $end);

        $calendarResources = [];
        foreach ($resources as $resource) {
            $calendarResources[] = [
                'id' => $resource->getId()->toRfc4122(),
                'title' => $resource->getName(),
                'extendedProps' => [
                    'type' => $resource->getType()->value,
                    'quantity' => $resource->getQuantity(),
                    'shareable' => $resource->isShareable(),
                ],
            ];
        }

        return $this->json($calendarResources);
    }

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

    private function getEventBorderColor(ScheduledEvent $event): string
    {
        if ($event->hasUnresolvedConflicts()) {
            return '#bd2130';
        }

        return $this->getEventColor($event);
    }
}
