<?php

namespace App\Domain\Application\Controller\Backoffice;

use App\Domain\Application\Entity\Enum\SubmissionStatus;
use App\Domain\Application\Entity\LarpApplicationChoice;
use App\Domain\Application\Entity\LarpApplicationVote;
use App\Domain\Application\Form\Filter\LarpApplicationChoiceFilterType;
use App\Domain\Application\Form\Filter\LarpApplicationFilterType;
use App\Domain\Application\Repository\LarpApplicationChoiceRepository;
use App\Domain\Application\Repository\LarpApplicationRepository;
use App\Domain\Application\Service\ApplicationMatchService;
use App\Domain\Application\Service\CharacterAllocationService;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Service\LarpApplicationDashboardService;
use App\Domain\Core\Service\SubmissionStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/larp/{larp}/applications', name: 'backoffice_larp_applications_')]
class CharacterApplicationsController extends BaseController
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

        // Paginate the applications
        $pagination = $this->getPagination($qb, $request);

        // Get applications with preloading from paginated results
        $applications = iterator_to_array($pagination->getItems());

        // Get dashboard statistics
        $dashboardStats = $dashboardService->getDashboardStats($larp, $applications);

        // Get legacy stats for compatibility
        $stats = $statsService->getStatsForLarp($larp);

        return $this->render('backoffice/larp/application/list.html.twig', [
            'larp' => $larp,
            'filterForm' => $filterForm->createView(),
            'applications' => $applications,
            'pagination' => $pagination,
            'factionStats' => $stats['factionStats'],
            'dashboard' => $dashboardStats,
        ]);
    }

    #[Route('/match', name: 'match', methods: ['GET'])]
    public function match(
        Request $request,
        Larp $larp,
        LarpApplicationChoiceRepository $repository,
        ApplicationMatchService $matchService,
    ): Response {
        $filterForm = $this->createForm(LarpApplicationChoiceFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        // Build base query
        $qb = $repository->createQueryBuilder('c')
            ->join('c.application', 'a')
            ->join('c.character', 'ch')
            ->where('a.larp = :larp')
            ->setParameter('larp', $larp);

        // Apply filters
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        // Apply sorting
        $sortBy = $request->query->get('sortBy', 'character');
        $sortOrder = $request->query->get('sortOrder', 'ASC');

        switch ($sortBy) {
            case 'character':
                $qb->orderBy('ch.title', $sortOrder);
                break;
            case 'priority':
                $qb->orderBy('c.priority', $sortOrder);
                break;
            case 'votes':
                $qb->orderBy('c.votes', $sortOrder);
                break;
            default:
                $qb->orderBy('ch.title', 'ASC');
        }

        // Add eager loading of relationships to the QueryBuilder
        $qb->addSelect('a', 'ch')
            ->leftJoin('a.user', 'user')
            ->addSelect('user');

        // Paginate at the QueryBuilder level (entities, not DTOs)
        $pagination = $this->getPagination($qb, $request);

        // Transform paginated entities to DTOs
        $characterMatches = $matchService->transformPaginatedChoicesToDTOs($pagination->getItems());
        $userVotes = $matchService->getUserVotes($this->getUser());

        return $this->render('backoffice/larp/application/match.html.twig', [
            'larp' => $larp,
            'characterMatches' => $characterMatches,
            'userVotes' => $userVotes,
            'filterForm' => $filterForm->createView(),
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
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
        $voteValue = (int) $request->request->get('vote');
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

        $voteRepository = $em->getRepository(LarpApplicationVote::class);
        $existingVote = $voteRepository->findOneBy([
            'choice' => $choice,
            'user' => $user
        ]);

        if ($existingVote instanceof LarpApplicationVote) {
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

        // Update total score
        $allVotes = $voteRepository->findBy(['choice' => $choice]);
        $totalScore = array_reduce($allVotes, fn ($sum, $v) => $sum + $v->getVote(), 0);
        $choice->setVotes($totalScore);

        $em->flush();

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
                'user' => $vote->getUser()->getUsername() ?? $vote->getUser()->getContactEmail(),
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

    #[Route('/{application}/view', name: 'view', methods: ['GET'])]
    public function view(
        Larp $larp,
        LarpApplication $application,
        EntityManagerInterface $em
    ): Response {
        // Load votes for all choices
        $voteRepository = $em->getRepository(LarpApplicationVote::class);
        $userVotes = [];

        $currentUser = $this->getUser();
        if ($currentUser instanceof UserInterface) {
            $votes = $voteRepository->findBy(['user' => $currentUser]);
            foreach ($votes as $vote) {
                $userVotes[$vote->getChoice()->getId()->toRfc4122()] = $vote;
            }
        }

        // Get all votes for this application's choices
        $allVotes = [];
        foreach ($application->getChoices() as $choice) {
            $choiceVotes = $voteRepository->findBy(['choice' => $choice], ['createdAt' => 'DESC']);
            $allVotes[$choice->getId()->toRfc4122()] = $choiceVotes;
        }

        return $this->render('backoffice/larp/application/view.html.twig', [
            'larp' => $larp,
            'application' => $application,
            'userVotes' => $userVotes,
            'allVotes' => $allVotes,
        ]);
    }

    #[Route('/{application}/accept-choice/{choice}', name: 'accept_choice', methods: ['POST'])]
    public function acceptChoice(
        Larp $larp,
        LarpApplication $application,
        LarpApplicationChoice $choice,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        // Verify the choice belongs to this application
        if ($choice->getApplication() !== $application) {
            throw $this->createAccessDeniedException('backoffice.larp.applications.choice_not_in_application');
        }

        // Check user has appropriate role (ORGANIZER or MAIN_STORY_WRITER)
        if (!$this->isGranted('ROLE_ORGANIZER') && !$this->isGranted('ROLE_MAIN_STORY_WRITER')) {
            $this->addFlash('error', 'backoffice.larp.applications.insufficient_permissions');
            return $this->redirectToRoute('backoffice_larp_applications_view', [
                'larp' => $larp->getId(),
                'application' => $application->getId()
            ]);
        }

        // Update application status to OFFERED
        $application->setStatus(SubmissionStatus::OFFERED);
        $em->flush();

        // Send email notification
        try {
            $this->sendCharacterAssignmentEmail(
                $mailer,
                $application,
                $larp,
                $choice->getCharacter()->getId()->toRfc4122(),
                $choice->getCharacter()->getTitle()
            );

            $this->addFlash('success', $this->translator->trans('backoffice.larp.applications.character_offered', [
                'character' => $choice->getCharacter()->getTitle(),
                'applicant' => $application->getContactEmail()
            ]));
        } catch (\Exception $e) {
            $this->addFlash('error', 'backoffice.larp.applications.email_failed: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_larp_applications_view', [
            'larp' => $larp->getId(),
            'application' => $application->getId()
        ]);
    }

    #[Route('/suggest-allocation', name: 'suggest_allocation', methods: ['GET'])]
    public function suggestAllocation(
        Larp $larp,
        CharacterAllocationService $allocationService
    ): Response {
        $suggestions = $allocationService->suggestAllocations($larp);

        return $this->render('backoffice/larp/application/suggest_allocation.html.twig', [
            'larp' => $larp,
            'suggestions' => $suggestions,
        ]);
    }

    #[Route('/accept-allocation', name: 'accept_allocation', methods: ['POST'])]
    public function acceptAllocation(
        Request $request,
        Larp $larp,
        EntityManagerInterface $em,
        LarpApplicationRepository $applicationRepository,
        MailerInterface $mailer
    ): Response {
        $allocations = $request->request->all('allocations');

        if (empty($allocations)) {
            $this->addFlash('error', 'backoffice.larp.applications.no_allocations_selected');
            return $this->redirectToRoute('backoffice_larp_applications_suggest_allocation', ['larp' => $larp->getId()]);
        }

        $successCount = 0;

        foreach ($allocations as $allocationData) {
            $applicationId = $allocationData['applicationId'] ?? null;
            $characterId = $allocationData['characterId'] ?? null;

            if (!$applicationId || !$characterId) {
                continue;
            }

            try {
                $application = $applicationRepository->find($applicationId);

                if (!$application) {
                    continue;
                }

                // Update application status to OFFERED
                $application->setStatus(SubmissionStatus::OFFERED);
                $em->persist($application);

                // Send email notification
                $this->sendCharacterAssignmentEmail(
                    $mailer,
                    $application,
                    $larp,
                    $characterId,
                    $allocationData['characterTitle'] ?? 'Character'
                );

                $successCount++;
            } catch (\Exception $e) {
                $this->addFlash('warning', 'backoffice.larp.applications.allocation_failed: ' . $e->getMessage());
            }
        }

        $em->flush();

        if ($successCount > 0) {
            $this->addFlash('success', $this->translator->trans('backoffice.larp.applications.allocations_sent', ['count' => $successCount]));
        }

        return $this->redirectToRoute('backoffice_larp_applications_list', ['larp' => $larp->getId()]);
    }

    private function sendCharacterAssignmentEmail(
        MailerInterface $mailer,
        $application,
        Larp $larp,
        string $characterId,
        string $characterTitle
    ): void {
        $confirmUrl = $this->generateUrl(
            'public_application_confirm_character',
            [
                'larp' => $larp->getId()->toRfc4122(),
                'application' => $application->getId()->toRfc4122(),
                'character' => $characterId,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $declineUrl = $this->generateUrl(
            'public_application_decline_character',
            [
                'larp' => $larp->getId()->toRfc4122(),
                'application' => $application->getId()->toRfc4122(),
                'character' => $characterId,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from('noreply@larpilot.com')
            ->to($application->getContactEmail())
            ->subject($this->translator->trans('email.character_assignment.subject', ['larp' => $larp->getTitle()]))
            ->html($this->renderView('emails/character_assignment.html.twig', [
                'larp' => $larp,
                'application' => $application,
                'characterTitle' => $characterTitle,
                'confirmUrl' => $confirmUrl,
                'declineUrl' => $declineUrl,
            ]));

        $mailer->send($email);
    }
}
