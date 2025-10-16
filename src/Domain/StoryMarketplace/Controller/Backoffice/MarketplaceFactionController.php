<?php

namespace App\Domain\StoryMarketplace\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryMarketplace\Form\Filter\FactionFilterType;
use App\Domain\StoryObject\Repository\FactionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/marketplace/factions', name: 'backoffice_larp_story_marketplace_factions_')]
class MarketplaceFactionController extends BaseController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Larp $larp,
        Request $request,
        FactionRepository $factionRepository
    ): Response {
        $filterForm = $this->createForm(FactionFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        $qb = $factionRepository->createQueryBuilder('f')
            ->where('f.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('f.title', 'ASC');

        // Apply filters using the filter builder updater
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        $pagination = $this->getPagination($qb, $request);

        return $this->render('backoffice/larp/marketplace/factions/list.html.twig', [
            'larp' => $larp,
            'factions' => $pagination,
            'filterForm' => $filterForm->createView(),
        ]);
    }
}
