<?php

namespace App\Domain\EventPlanning\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\EventPlanning\Entity\ScheduledEvent;
use App\Domain\EventPlanning\Form\Filter\ScheduledEventFilterType;
use App\Domain\EventPlanning\Form\ScheduledEventType;
use App\Domain\EventPlanning\Repository\ScheduledEventRepository;
use App\Domain\EventPlanning\Service\ConflictDetectionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/event-planner/event', name: 'backoffice_event_planner_event_')]
class ScheduledEventController extends BaseController
{
    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request, Larp $larp, ScheduledEventRepository $repository): Response
    {
        $filterForm = $this->createForm(ScheduledEventFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        $qb = $repository->createQueryBuilder('e')
            ->where('e.larp = :larp')
            ->setParameter('larp', $larp);

        // Apply filters using FilterBuilderUpdater
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        // Sorting
        $sort = $request->query->get('sort', 'startTime');
        $dir = $request->query->get('dir', 'asc');
        $qb->orderBy('e.' . $sort, $dir);

        $pagination = $this->getPagination($qb, $request);

        return $this->render('backoffice/event_planner/event/list.html.twig', [
            'larp' => $larp,
            'events' => $pagination,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/{event}', name: 'modify', defaults: ['event' => null], methods: ['GET', 'POST'])]
    public function modify(
        Request $request,
        Larp $larp,
        ScheduledEventRepository $repository,
        ConflictDetectionService $conflictDetectionService,
        ?ScheduledEvent $event = null
    ): Response {
        $isNew = !($event instanceof ScheduledEvent);

        if ($isNew) {
            $event = new ScheduledEvent();
            $event->setLarp($larp);
            /** @var User|null $currentUser */
            $currentUser = $this->getUser();
            if ($currentUser instanceof User) {
                $event->setCreatedBy($currentUser);
            }

            // Set default times to LARP start date if available
            $endDate = $larp->getEndDate();
            if ($larp->getStartDate() && $endDate) {
                $startTime = \DateTime::createFromInterface($endDate);
                $endTime = \DateTime::createFromInterface($endDate);
                $endTime->modify('+1 hour');
                $event->setStartTime($startTime);
                $event->setEndTime($endTime);
            }
        }

        $form = $this->createForm(ScheduledEventType::class, $event, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($event);

            // Detect conflicts
            $conflicts = $conflictDetectionService->detectConflicts($event);
            if (!empty($conflicts)) {
                $this->addFlash(
                    'warning',
                    $this->translator->trans('event_planner.event.conflicts_detected', [
                        'count' => count($conflicts),
                    ])
                );
            } else {
                $this->addFlash('success', $this->translator->trans('success_save'));
            }

            return $this->redirectToRoute('backoffice_event_planner_event_view', [
                'larp' => $larp->getId(),
                'event' => $event->getId(),
            ]);
        }

        return $this->render('backoffice/event_planner/event/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'event' => $event,
            'isNew' => $isNew,
        ]);
    }

    #[Route('/{event}/view', name: 'view', methods: ['GET'])]
    public function view(Larp $larp, ScheduledEvent $event, ConflictDetectionService $conflictDetectionService): Response
    {
        $conflicts = $conflictDetectionService->detectConflicts($event);

        return $this->render('backoffice/event_planner/event/view.html.twig', [
            'larp' => $larp,
            'event' => $event,
            'conflicts' => $conflicts,
        ]);
    }

    #[Route('/{event}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Larp $larp,
        ScheduledEventRepository $repository,
        ScheduledEvent $event
    ): Response {
        $repository->remove($event);
        $this->addFlash('success', $this->translator->trans('success_delete'));

        return $this->redirectToRoute('backoffice_event_planner_event_list', [
            'larp' => $larp->getId(),
        ]);
    }
}
