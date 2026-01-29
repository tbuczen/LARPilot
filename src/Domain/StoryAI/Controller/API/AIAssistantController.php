<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Controller\API;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\DTO\ChatMessage;
use App\Domain\StoryAI\Service\Query\RAGQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/larp/{larp}/ai', name: 'api_larp_ai_')]
#[IsGranted('ROLE_USER')]
class AIAssistantController extends AbstractController
{
    public function __construct(
        private readonly RAGQueryService $ragQueryService,
    ) {
    }

    /**
     * Execute an AI query against the LARP's story content.
     */
    #[Route('/query', name: 'query', methods: ['POST'])]
    #[IsGranted('LARP_VIEW', subject: 'larp')]
    public function query(Request $request, Larp $larp): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['query'])) {
            return $this->json([
                'error' => 'Query is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $query = trim($data['query']);

        // Parse conversation history if provided
        $conversationHistory = [];
        if (isset($data['history']) && is_array($data['history'])) {
            foreach ($data['history'] as $message) {
                if (isset($message['role'], $message['content'])) {
                    $conversationHistory[] = new ChatMessage(
                        $message['role'],
                        $message['content']
                    );
                }
            }
        }

        try {
            $result = $this->ragQueryService->query(
                $query,
                $larp,
                $conversationHistory,
                maxSources: (int) ($data['maxSources'] ?? 10),
                minSimilarity: (float) ($data['minSimilarity'] ?? 0.4),
            );

            return $this->json([
                'response' => $result->response,
                'sources' => array_map(fn ($s) => [
                    'title' => $s->title,
                    'type' => $s->entityType ?? 'Lore',
                    'similarity' => $s->getSimilarityPercent(),
                    'preview' => $s->getContentPreview(150),
                    'entityId' => $s->entityId,
                ], $result->sources),
                'usage' => $result->usage,
                'model' => $result->model,
                'processingTime' => round($result->processingTime, 2),
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Failed to process query',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Search for story content without AI completion.
     */
    #[Route('/search', name: 'search', methods: ['POST'])]
    #[IsGranted('LARP_VIEW', subject: 'larp')]
    public function search(Request $request, Larp $larp): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['query'])) {
            return $this->json([
                'error' => 'Query is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $query = trim($data['query']);

        try {
            $results = $this->ragQueryService->search(
                $query,
                $larp,
                limit: (int) ($data['limit'] ?? 10),
                minSimilarity: (float) ($data['minSimilarity'] ?? 0.4),
            );

            return $this->json([
                'results' => array_map(fn ($r) => [
                    'id' => $r->id,
                    'title' => $r->title,
                    'type' => $r->type,
                    'entityType' => $r->entityType,
                    'entityId' => $r->entityId,
                    'similarity' => $r->getSimilarityPercent(),
                    'preview' => $r->getContentPreview(200),
                    'metadata' => $r->metadata,
                ], $results),
                'count' => count($results),
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Search failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get story arc suggestions for a character.
     */
    #[Route('/suggest/story-arc', name: 'suggest_story_arc', methods: ['POST'])]
    #[IsGranted('LARP_VIEW', subject: 'larp')]
    public function suggestStoryArc(Request $request, Larp $larp): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $elementType = $data['elementType'] ?? 'character';
        $elementTitle = $data['elementTitle'] ?? '';
        $elementContext = $data['elementContext'] ?? '';

        if (empty($elementTitle)) {
            return $this->json([
                'error' => 'Element title is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->ragQueryService->suggestStoryArc(
                $elementType,
                $elementTitle,
                $elementContext,
                $larp
            );

            return $this->json([
                'suggestion' => $result->response,
                'sources' => array_map(fn ($s) => [
                    'title' => $s->title,
                    'type' => $s->entityType ?? 'Lore',
                ], $result->sources),
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Failed to generate suggestion',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get relationship suggestions for a character.
     */
    #[Route('/suggest/relationships', name: 'suggest_relationships', methods: ['POST'])]
    #[IsGranted('LARP_VIEW', subject: 'larp')]
    public function suggestRelationships(Request $request, Larp $larp): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $characterName = $data['characterName'] ?? '';
        $characterContext = $data['characterContext'] ?? '';

        if (empty($characterName)) {
            return $this->json([
                'error' => 'Character name is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->ragQueryService->suggestRelationships(
                $characterName,
                $characterContext,
                $larp
            );

            return $this->json([
                'suggestion' => $result->response,
                'sources' => array_map(fn ($s) => [
                    'title' => $s->title,
                    'type' => $s->entityType ?? 'Lore',
                ], $result->sources),
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Failed to generate suggestion',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Find connections between multiple story elements.
     */
    #[Route('/find-connections', name: 'find_connections', methods: ['POST'])]
    #[IsGranted('LARP_VIEW', subject: 'larp')]
    public function findConnections(Request $request, Larp $larp): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $elementNames = $data['elementNames'] ?? [];

        if (empty($elementNames) || count($elementNames) < 2) {
            return $this->json([
                'error' => 'At least two element names are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->ragQueryService->findConnections(
                $elementNames,
                $larp
            );

            return $this->json([
                'analysis' => $result->response,
                'sources' => array_map(fn ($s) => [
                    'title' => $s->title,
                    'type' => $s->entityType ?? 'Lore',
                ], $result->sources),
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Failed to analyze connections',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Analyze plot consistency.
     */
    #[Route('/analyze/consistency', name: 'analyze_consistency', methods: ['POST'])]
    #[IsGranted('LARP_VIEW', subject: 'larp')]
    public function analyzeConsistency(Request $request, Larp $larp): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $plotElement = $data['plotElement'] ?? '';

        if (empty($plotElement)) {
            return $this->json([
                'error' => 'Plot element description is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->ragQueryService->analyzePlotConsistency(
                $plotElement,
                $larp
            );

            return $this->json([
                'analysis' => $result->response,
                'sources' => array_map(fn ($s) => [
                    'title' => $s->title,
                    'type' => $s->entityType ?? 'Lore',
                ], $result->sources),
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Failed to analyze consistency',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
