<?php

namespace App\Domain\StoryMarketplace\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryMarketplace\Form\Filter\MarketplaceFilterType;
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
        $filterForm = $this->createForm(MarketplaceFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        $qb = $eventRepository->createQueryBuilder('e')
            ->where('e.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('e.title', 'ASC');

        // Apply tag filtering if provided
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $selectedTags = $filterForm->get('tags')->getData();
            if ($selectedTags && !$selectedTags->isEmpty()) {
                $qb = $eventRepository->createEventsByTagsQueryBuilder($larp, $selectedTags->toArray());
            }
        }

        $pagination = $this->getPagination($qb, $request);

        return $this->render('backoffice/larp/marketplace/events/list.html.twig', [
            'larp' => $larp,
            'events' => $pagination,
            'filterForm' => $filterForm->createView(),
        ]);
    }
}
