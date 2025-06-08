<?php

namespace App\Controller\Backoffice\Story;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\StoryObject\Relation;
use App\Form\Filter\StoryGraphFilterType;
use App\Repository\StoryObject\StoryObjectRepository;
use App\Service\Larp\StoryObjectRelationExplorer;
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
//        $qb = $repository->createQueryBuilder('c');
//        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
//        $qb->andWhere('c.larp = :larp')
//            ->setParameter('larp', $larp)
//            ->andWhere('c NOT INSTANCE OF ' . Relation::class)
//            ;
//        $objects = $qb->getQuery()->getResult();

        $data = $filterForm->getData() ?: [];

        $objects = $repository->findForGraph(
            $larp,
            $data['thread'] ?? [],
            $data['involvedCharacters'] ?? [],
            $data['involvedFactions'] ?? [],
        );

        return $this->render('backoffice/larp/story/main.html.twig', [
            'larp' => $larp,
            'filterForm' => $filterForm->createView(),
            'graph' => $explorer->getGraphFromResults($objects),
        ]);
    }

}