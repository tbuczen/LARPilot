<?php

namespace App\Controller\Backoffice\Story;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\StoryObject\Event;
use App\Form\Filter\EventFilterType;
use App\Form\EventType;
use App\Helper\Logger;
use App\Repository\StoryObject\EventRepository;
use App\Service\Integrations\IntegrationManager;
use App\Service\Larp\LarpManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/event/', name: 'backoffice_larp_story_event_')]

class EventsController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET', 'POST'])]
    public function list(Request $request, Larp $larp, EventRepository $repository): Response
    {
        $filterForm = $this->createForm(EventFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);
        $qb = $repository->createQueryBuilder('c');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $sort = $request->query->get('sort', 'title');
        $dir = $request->query->get('dir', 'asc');

        $qb->orderBy('c.' . $sort, $dir);
        $qb->andWhere('c.larp = :larp')
            ->setParameter('larp', $larp);

        return $this->render('backoffice/larp/event/list.html.twig', [
            'filterForm' => $filterForm->createView(),
            'events' => $qb->getQuery()->getResult(),
            'larp' => $larp,
        ]);
    }

    #[Route('{event}', name: 'modify', defaults: ['event' => null], methods: ['GET', 'POST'])]
    public function modify(
        LarpManager        $larpManager,
        IntegrationManager $integrationManager,
        Request            $request,
        Larp               $larp,
        EventRepository    $eventRepository,
        ?Event             $event = null,
    ): Response
    {

        $new = false;
        if (!$event) {
            $event = new Event();
            $event->setLarp($larp);
            $new = true;
        }

        $form = $this->createForm(EventType::class, $event, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eventRepository->save($event);

            $this->processIntegrationsForStoryObject($larpManager, $larp, $integrationManager, $new, $event);

            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
            return $this->redirectToRoute('backoffice_larp_story_event_list', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/larp/events/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
        ]);
    }

    #[Route('{event}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(
        LarpManager             $larpManager,
        IntegrationManager      $integrationManager,
        Larp                    $larp,
        Request                 $request,
        EventRepository $eventRepository,
        Event           $event,
    ): Response
    {
        $deleteIntegrations = $request->query->getBoolean('integrations');

        if ($deleteIntegrations) {
            $integrations = $larpManager->getIntegrationsForLarp($larp);
            foreach ($integrations as $integration) {
                try {
                    $integrationService = $integrationManager->getService($integration);
                    $integrationService->removeStoryObject($integration, $event);
                } catch (\Throwable $e) {
                    Logger::get()->error($e->getMessage(), $e->getTrace());
                    $this->addFlash('danger', 'Failed to remove from ' . $integration->getProvider()->name . '. Event not deleted.');
                    return $this->redirectToRoute('backoffice_larp_story_event_list', [
                        'larp' => $larp->getId(),
                    ]);
                }
            }
        }

        $eventRepository->remove($event);

        $this->addFlash('success', $this->translator->trans('backoffice.common.success_delete'));

        return $this->redirectToRoute('backoffice_larp_story_event_list', [
            'larp' => $larp->getId(),
        ]);
    }

    #[Route('import/file', name: 'import_file', methods: ['GET', 'POST'])]
    public function importFile(Larp $larp, LarpManager $larpManager): Response
    {
        return new Response('TODO:: Import from file csv/xlsx');
    }
}