<?php

namespace App\Domain\StoryMarketplace\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Form\Filter\ThreadFilterType;
use App\Domain\StoryObject\Repository\ThreadRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/marketplace/threads', name: 'backoffice_larp_story_marketplace_threads_')]
class MarketplaceThreadController extends BaseController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Larp $larp,
        Request $request,
        ThreadRepository $threadRepository
    ): Response {
        $filterForm = $this->createForm(ThreadFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        $qb = $threadRepository->createQueryBuilder('t')
            ->where('t.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('t.title', 'ASC');

        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        $pagination = $this->getPagination($qb, $request);

        return $this->render('backoffice/larp/marketplace/threads/list.html.twig', [
            'larp' => $larp,
            'threads' => $pagination,
            'filterForm' => $filterForm->createView(),
        ]);
    }
}
