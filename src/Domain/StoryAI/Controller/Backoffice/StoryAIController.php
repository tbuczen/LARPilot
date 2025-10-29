<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Controller\Backoffice;

use App\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\Repository\AIGenerationRequestRepository;
use App\Domain\StoryAI\Service\ThreadSuggestionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Main controller for AI-powered story features.
 *
 * @TODO Phase 1: Add basic UI for thread suggestions
 * @TODO Phase 3: Add gap analysis UI
 * @TODO Phase 4: Add quest/event suggestion UI
 * @TODO Phase 5: Add settings/configuration UI
 */
#[Route('/backoffice/larp/{larp}/ai', name: 'backoffice_larp_ai_')]
#[IsGranted('ROLE_USER')]
class StoryAIController extends BaseController
{
    public function __construct(
        private readonly AIGenerationRequestRepository $requestRepository,
        private readonly ThreadSuggestionService $threadSuggestionService,
    ) {
    }

    /**
     * AI dashboard - overview of AI features and recent requests.
     *
     * @TODO Phase 1: Implement basic dashboard
     */
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(Larp $larp): Response
    {
        $recentRequests = $this->requestRepository->findByLarp($larp, limit: 10);

        return $this->render('backoffice/story_ai/dashboard.html.twig', [
            'larp' => $larp,
            'recent_requests' => $recentRequests,
        ]);
    }

    /**
     * Thread suggestions page.
     *
     * @TODO Phase 1: Implement suggestion form
     * @TODO Phase 1: Implement suggestion display
     * @TODO Phase 4: Add "Accept" functionality
     */
    #[Route('/threads', name: 'thread_suggestions', methods: ['GET', 'POST'])]
    public function threadSuggestions(Larp $larp, Request $request): Response
    {
        // @TODO: Create form for filters (characters, tags)
        // @TODO: Handle form submission
        // @TODO: Call ThreadSuggestionService
        // @TODO: Display results

        return $this->render('backoffice/story_ai/suggestions/threads.html.twig', [
            'larp' => $larp,
            // @TODO: Add form and suggestions
        ]);
    }

    /**
     * Gap analysis page.
     *
     * @TODO Phase 3: Implement gap analysis
     */
    #[Route('/gaps', name: 'gap_analysis', methods: ['GET', 'POST'])]
    public function gapAnalysis(Larp $larp): Response
    {
        // @TODO: Implement gap analysis service
        // @TODO: Display results

        return $this->render('backoffice/story_ai/gap_analysis/index.html.twig', [
            'larp' => $larp,
        ]);
    }

    /**
     * Generation history page.
     *
     * @TODO Phase 1: Implement history list with filters
     */
    #[Route('/history', name: 'history', methods: ['GET'])]
    public function history(Larp $larp): Response
    {
        $requests = $this->requestRepository->findByLarp($larp, limit: 50);

        return $this->render('backoffice/story_ai/history/list.html.twig', [
            'larp' => $larp,
            'requests' => $requests,
        ]);
    }
}
