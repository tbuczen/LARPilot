<?php

namespace App\Domain\EventPlanning\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\EventPlanning\Entity\ScheduledEvent;
use App\Domain\EventPlanning\Form\Filter\ScheduledEventFilterType;
use App\Domain\EventPlanning\Repository\PlanningResourceRepository;
use App\Domain\EventPlanning\Repository\ScheduledEventRepository;
use App\Domain\EventPlanning\Service\CalendarEventFormatter;
use App\Domain\EventPlanning\Service\ScheduledEventService;
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
        $defaultEnd = $larp->getEndDate() ?? (new \DateTime($defaultStart->format('Y-m-d H:i:s')))->modify('+7 days');

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
        ScheduledEventRepository $eventRepository,
        CalendarEventFormatter $formatter
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
            $calendarEvents[] = $formatter->formatEvent($event, $larp);
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

    #[Route('/events/create', name: 'create_event_api', methods: ['POST'])]
    public function createEventApi(
        Request $request,
        Larp $larp,
        ScheduledEventService $eventService,
        CalendarEventFormatter $formatter
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid JSON in request body',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate required fields
            if (empty($data['start']) || empty($data['end'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Missing required fields: start and end times',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Parse dates from FullCalendar ISO format
            $start = new \DateTimeImmutable($data['start']);
            $end = new \DateTimeImmutable($data['end']);

            // Create event via service
            $event = $eventService->createEvent(
                $larp,
                $start,
                $end,
                $data['title'] ?? null
            );

            // Return event data in FullCalendar format using formatter
            // Use modify route for newly created events to allow immediate editing
            return $this->json([
                'success' => true,
                'event' => $formatter->formatEvent($event, $larp, 'backoffice_event_planner_event_modify'),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to create event: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/events/{event}', name: 'update_event_api', methods: ['PATCH'])]
    public function updateEventApi(
        Request $request,
        Larp $larp,
        ScheduledEvent $event,
        ScheduledEventService $eventService
    ): JsonResponse {
        // Verify event belongs to this LARP
        if ($event->getLarp() !== $larp) {
            return $this->json([
                'success' => false,
                'message' => 'Event does not belong to this LARP',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid JSON in request body',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate required fields
            if (empty($data['start']) || empty($data['end'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Missing required fields: start and end times',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Parse dates from FullCalendar ISO format
            $start = new \DateTimeImmutable($data['start']);
            $end = new \DateTimeImmutable($data['end']);

            // Update event times via service
            $eventService->updateEventTimes($event, $start, $end);

            // Return success
            return $this->json([
                'success' => true,
                'message' => 'Event times updated successfully',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to update event: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
