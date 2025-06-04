<?php

namespace App\Controller\Backoffice\Story;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\StoryObject\StoryObject;
use App\Form\Filter\StoryGraphFilterType;
use App\Repository\StoryObject\StoryObjectRepository;
use App\Service\Larp\StoryObjectRelationExplorer;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story', name: 'backoffice_larp_story_')]
class StoryController extends BaseController
{


    #[Route('main', name: 'main', methods: ['GET', 'POST'])]
    public function list(
        Request $request,
        Larp $larp,
        StoryObjectRepository $repository,
        StoryObjectRelationExplorer $explorer): Response
    {
        $filterForm = $this->createForm(StoryGraphFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);
        $qb = $repository->createQueryBuilder('c');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        $objects = $qb->getQuery()->getResult();
        return $this->render('backoffice/larp/story/main.html.twig', [
            'larp' => $larp,
            'filterForm' => $filterForm->createView(),
            'graph' => $explorer->getGraphFromResults($objects),
        ]);
    }

}