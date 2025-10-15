<?php

namespace App\Domain\StoryMarketplace\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryMarketplace\Form\Filter\MarketplaceFilterType;
use App\Domain\StoryObject\Repository\QuestRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/marketplace/quests', name: 'backoffice_larp_story_marketplace_quests_')]
class MarketplaceQuestController extends BaseController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Larp $larp,
        Request $request,
        QuestRepository $questRepository
    ): Response {
        $filterForm = $this->createForm(MarketplaceFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        $qb = $questRepository->createQueryBuilder('q')
            ->where('q.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('q.title', 'ASC');

        // Apply tag filtering if provided
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $selectedTags = $filterForm->get('tags')->getData();
            if ($selectedTags && !$selectedTags->isEmpty()) {
                $qb = $questRepository->createQuestsByTagsQueryBuilder($larp, $selectedTags->toArray());
            }
        }

        $pagination = $this->getPagination($qb, $request);

        return $this->render('backoffice/larp/marketplace/quests/list.html.twig', [
            'larp' => $larp,
            'quests' => $pagination,
            'filterForm' => $filterForm->createView(),
        ]);
    }
}
