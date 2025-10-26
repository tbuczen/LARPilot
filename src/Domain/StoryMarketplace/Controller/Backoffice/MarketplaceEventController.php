<?php

namespace App\Domain\StoryMarketplace\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Form\Filter\EventFilterType;
use App\Domain\StoryObject\Repository\EventRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/marketplace/events', name: 'backoffice_larp_story_marketplace_events_')]
class MarketplaceEventController extends BaseController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Larp $larp,
        Request $request,
        EventRepository $eventRepository
    ): Response {
        $filterForm = $this->createForm(EventFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        $qb = $eventRepository->createQueryBuilder('e')
            ->where('e.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('e.title', 'ASC');

        // Apply filters using FilterBuilderUpdater
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        $pagination = $this->getPagination($qb, $request);

        return $this->render('backoffice/larp/marketplace/events/list.html.twig', [
            'larp' => $larp,
            'events' => $pagination,
            'filterForm' => $filterForm->createView(),
        ]);
    }
}
