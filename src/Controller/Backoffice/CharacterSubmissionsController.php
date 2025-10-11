<?php

namespace App\Controller\Backoffice;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\LarpApplicationChoice;
use App\Entity\LarpApplicationVote;
use App\Form\Filter\LarpApplicationChoiceFilterType;
use App\Form\Filter\LarpApplicationFilterType;
use App\Repository\LarpApplicationChoiceRepository;
use App\Repository\LarpApplicationRepository;
use App\Service\Larp\LarpApplicationDashboardService;
use App\Service\Larp\SubmissionStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/larp/{larp}/applications', name: 'backoffice_larp_applications_')]
class CharacterSubmissionsController extends BaseController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Request $request,
        Larp $larp,
        LarpApplicationRepository $repository,
        LarpApplicationDashboardService $dashboardService,
        SubmissionStatsService $statsService
    ): Response {
        $filterForm = $this->createForm(LarpApplicationFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        // Build a query with filters
        $qb = $repository->createQueryBuilder('a')
            ->leftJoin('a.choices', 'choice')
            ->leftJoin('choice.character', 'character')
            ->leftJoin('character.factions', 'faction')
            ->addSelect('choice', 'character', 'faction')
            ->andWhere('a.larp = :larp')
            ->setParameter('larp', $larp);

        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $qb->orderBy('a.createdAt', 'DESC');

        // Get applications with preloading
        $applications = $dashboardService->getApplicationsWithPreloading($larp, $qb);

        // Get dashboard statistics
        $dashboardStats = $dashboardService->getDashboardStats($larp, $applications);

        // Get legacy stats for compatibility
        $stats = $statsService->getStatsForLarp($larp);

        return $this->render('backoffice/larp/application/list.html.twig', [
            'larp' => $larp,
            'filterForm' => $filterForm->createView(),
            'applications' => $applications,
            'factionStats' => $stats['factionStats'],
            'dashboard' => $dashboardStats,
        ]);
    }

    #[Route('/match', name: 'match', methods: ['GET'])]
    public function match(
        Request $request,
        Larp $larp,
        EntityManagerInterface $em,
        LarpApplicationChoiceRepository $repository,
    ): Response {
        $filterForm = $this->createForm(LarpApplicationChoiceFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);
        $qb = $repository->createQueryBuilder('c');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $pagination = $this->getPagination($qb, $request);

        // Group choices by character
        $grouped = [];
        foreach ($pagination as $choice) {
            $id = $choice->getCharacter()->getId()->toRfc4122();
            $grouped[$id]['character'] = $choice->getCharacter();
            $grouped[$id]['choices'][] = $choice;
        }

        // Get voting data for current user
        $voteRepository = $em->getRepository(LarpApplicationVote::class);
        $userVotes = [];
        if ($this->getUser() instanceof UserInterface) {
            $votes = $voteRepository->findBy(['user' => $this->getUser()]);
            foreach ($votes as $vote) {
                $userVotes[$vote->getChoice()->getId()->toRfc4122()] = $vote;
            }
        }

        // Calculate vote statistics for each choice
        $voteStats = [];
        if (!empty($grouped)) {
            $allChoices = array_merge(...array_column($grouped, 'choices'));
            foreach ($allChoices as $choice) {
                $choiceId = $choice->getId()->toRfc4122();
                $votes = $voteRepository->findBy(['choice' => $choice]);

                $upvotes = 0;
                $downvotes = 0;
                $voteDetails = [];

                foreach ($votes as $vote) {
                    if ($vote->isUpvote()) {
                        $upvotes++;
                    } else {
                        $downvotes++;
                    }

                    $voteDetails[] = [
                        'user' => $vote->getUser()->getUsername(),
                        'vote' => $vote->getVote(),
                        'justification' => $vote->getJustification(),
                        'createdAt' => $vote->getCreatedAt()
                    ];
                }

                $voteStats[$choiceId] = [
                    'upvotes' => $upvotes,
                    'downvotes' => $downvotes,
                    'total' => $upvotes - $downvotes,
                    'details' => $voteDetails
                ];
            }
        }

        return $this->render('backoffice/larp/application/match.html.twig', [
            'larp' => $larp,
            'choices' => $grouped,
            'userVotes' => $userVotes,
            'voteStats' => $voteStats,
            'filterForm' => $filterForm->createView(),
            'pagination' => $pagination,
        ]);
    }

    #[Route('/vote/{choice}', name: 'vote', methods: ['POST'])]
    public function vote(
        Larp $larp,
        LarpApplicationChoice $choice,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $voteValue = (int) $request->request->get('vote'); // 1 for upvote, -1 for downvote
        $justification = $request->request->get('justification', '');

        if (!in_array($voteValue, [1, -1])) {
            $this->addFlash('error', 'backoffice.larp.applications.invalid_vote');
            return $this->redirectToRoute('backoffice_larp_applications_match', ['larp' => $larp->getId()]);
        }

        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            $this->addFlash('error', 'backoffice.larp.applications.login_required');
            return $this->redirectToRoute('backoffice_larp_applications_match', ['larp' => $larp->getId()]);
        }

        // Check if user has already voted on this choice
        $voteRepository = $em->getRepository(LarpApplicationVote::class);
        $existingVote = $voteRepository->findOneBy([
            'choice' => $choice,
            'user' => $user
        ]);

        if ($existingVote instanceof LarpApplicationVote) {
            // Update existing vote
            $existingVote->setVote($voteValue);
            $existingVote->setJustification($justification);
            $existingVote->setCreatedAt(new \DateTime());
        } else {
            $vote = new LarpApplicationVote();
            $vote->setChoice($choice);
            $vote->setUser($user);
            $vote->setVote($voteValue);
            $vote->setJustification($justification);
            $em->persist($vote);
        }

        // Update the choice's total votes (for backward compatibility)
        $allVotes = $voteRepository->findBy(['choice' => $choice]);
        $totalScore = 0;
        foreach ($allVotes as $vote) {
            $totalScore += $vote->getVote();
        }
        $choice->setVotes($totalScore);

        $em->flush();

        $voteType = $voteValue > 0 ? 'upvote' : 'downvote';
        $this->addFlash('success', 'backoffice.larp.applications.vote_recorded');
        
        return $this->redirectToRoute('backoffice_larp_applications_match', ['larp' => $larp->getId()]);
    }

    #[Route('/vote/{choice}/details', name: 'vote_details', methods: ['GET'])]
    public function voteDetails(
        Larp $larp,
        LarpApplicationChoice $choice,
        EntityManagerInterface $em
    ): JsonResponse {
        $voteRepository = $em->getRepository(LarpApplicationVote::class);
        $votes = $voteRepository->findBy(['choice' => $choice], ['createdAt' => 'DESC']);
        
        $voteDetails = [];
        foreach ($votes as $vote) {
            $voteDetails[] = [
                'user' => $vote->getUser()->getUsername(),
                'vote' => $vote->getVote(),
                'justification' => $vote->getJustification(),
                'createdAt' => $vote->getCreatedAt()->format('Y-m-d H:i:s'),
                'isUpvote' => $vote->isUpvote()
            ];
        }
        
        return new JsonResponse([
            'votes' => $voteDetails,
            'character' => $choice->getCharacter()->getTitle(),
            'applicant' => $choice->getApplication()->getContactEmail()
        ]);
    }
}
