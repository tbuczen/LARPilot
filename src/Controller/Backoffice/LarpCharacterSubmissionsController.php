<?php

namespace App\Controller\Backoffice;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\LarpApplicationChoice;
use App\Form\Filter\LarpApplicationFilterType;
use App\Repository\LarpApplicationChoiceRepository;
use App\Repository\LarpApplicationRepository;
use App\Service\Larp\SubmissionStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/applications', name: 'backoffice_larp_applications_')]
class LarpCharacterSubmissionsController extends BaseController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Request $request,
        Larp $larp,
        LarpApplicationRepository $repository,
        SubmissionStatsService $statsService
    ): Response {
        $filterForm = $this->createForm(LarpApplicationFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        $qb = $repository->createQueryBuilder('a')
            ->leftJoin('a.choices', 'choice')
            ->leftJoin('choice.character', 'character')
            ->leftJoin('character.factions', 'faction')
            ->addSelect('choice', 'character', 'faction')
            ->andWhere('a.larp = :larp')
            ->setParameter('larp', $larp);

        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        $qb->orderBy('a.createdAt', 'DESC');

        $stats = $statsService->getStatsForLarp($larp);

        return $this->render('backoffice/larp/application/list.html.twig', [
            'larp' => $larp,
            'filterForm' => $filterForm->createView(),
            'applications' => $qb->getQuery()->getResult(),
            'missing' => $stats['missing'],
            'factionStats' => $stats['factionStats'],
        ]);
    }

    #[Route('match', name: 'match', methods: ['GET'])]
    public function match(
        Larp $larp,
        EntityManagerInterface $em
    ): Response {
        $choiceRepository = $em->getRepository(LarpApplicationChoice::class);
        $choices = $choiceRepository->createQueryBuilder('c')
            ->join('c.application', 'a')
            ->join('c.character', 'ch')
            ->leftJoin('ch.factions', 'f')
            ->addSelect('a', 'ch', 'f')
            ->andWhere('a.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('ch.title', 'ASC')
            ->addOrderBy('c.priority', 'DESC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($choices as $choice) {
            $id = $choice->getCharacter()->getId()->toRfc4122();
            $grouped[$id]['character'] = $choice->getCharacter();
            $grouped[$id]['choices'][] = $choice;
        }

        return $this->render('backoffice/larp/application/match.html.twig', [
            'larp' => $larp,
            'choices' => $grouped,
        ]);
    }

    #[Route('vote/{choice}', name: 'vote', methods: ['POST'])]
    public function vote(Larp $larp, LarpApplicationChoice $choice, EntityManagerInterface $em): Response
    {
        $choice->setVotes($choice->getVotes() + 1);
        $em->flush();

        $this->addFlash('success', 'backoffice.larp.applications.vote_success');
        return $this->redirectToRoute('backoffice_larp_applications_match', ['larp' => $larp->getId()]);
    }
}
