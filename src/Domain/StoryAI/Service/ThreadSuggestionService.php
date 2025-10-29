<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\Infrastructure\Entity\User;
use App\Domain\StoryAI\DTO\AIRequestDTO;
use App\Domain\StoryAI\DTO\ThreadSuggestionDTO;
use App\Domain\StoryAI\Entity\AIGenerationRequest;
use App\Domain\StoryAI\Entity\AIGenerationResult;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Generates thread suggestions using AI.
 *
 * @TODO Phase 1: Implement basic prompt building
 * @TODO Phase 2: Add vector DB context retrieval
 * @TODO Phase 4: Add suggestion ranking logic
 */
class ThreadSuggestionService
{
    public function __construct(
        private readonly AIProviderInterface $aiProvider,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Generate thread suggestions for a LARP.
     *
     * @param int[] $characterIds Optional character IDs to focus on
     * @param string[] $tags Optional tags to filter by
     * @return ThreadSuggestionDTO[]
     */
    public function generateSuggestions(
        Larp $larp,
        User $user,
        array $characterIds = [],
        array $tags = [],
    ): array {
        $startTime = microtime(true);

        // @TODO Phase 1: Build context from LARP data
        // @TODO Phase 2: Use vector DB to get relevant context
        $context = $this->buildContext($larp, $characterIds, $tags);

        // @TODO Phase 1: Build prompt using template
        $prompt = $this->buildPrompt($larp, $context, $tags);

        // Create request entity for tracking
        $request = new AIGenerationRequest();
        $request->setLarp($larp);
        $request->setRequestedBy($user);
        $request->setFeatureType('thread_suggestion');
        $request->setProvider($this->aiProvider->getName());
        $request->setParameters([
            'character_ids' => $characterIds,
            'tags' => $tags,
        ]);

        try {
            // Call AI provider
            $aiRequest = new AIRequestDTO(
                prompt: $prompt,
                systemPrompt: 'You are a creative LARP story consultant.',
                maxTokens: 4096,
                temperature: 0.8, // Higher temperature for creativity
            );

            $response = $this->aiProvider->generate($aiRequest);

            // Update request with metrics
            $request->setTokensUsed($response->tokensUsed);
            $request->setResponseTimeMs($response->responseTimeMs);

            // Parse response
            $suggestions = $this->parseResponse($response->content);

            // Store results
            foreach ($suggestions as $suggestion) {
                $result = new AIGenerationResult();
                $result->setRequest($request);
                $result->setContent([
                    'title' => $suggestion->title,
                    'summary' => $suggestion->summary,
                    'acts' => $suggestion->acts,
                    'character_ids' => $suggestion->characterIds,
                    'tags' => $suggestion->tags,
                    'rationale' => $suggestion->rationale,
                ]);
                $this->entityManager->persist($result);
            }

            $this->entityManager->persist($request);
            $this->entityManager->flush();

            return $suggestions;
        } catch (\Exception $e) {
            $request->setErrorMessage($e->getMessage());
            $request->setResponseTimeMs((int) ((microtime(true) - $startTime) * 1000));
            $this->entityManager->persist($request);
            $this->entityManager->flush();

            throw new \RuntimeException('Failed to generate thread suggestions: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @TODO Phase 1: Implement context building
     * @TODO Phase 2: Replace with vector DB retrieval
     */
    private function buildContext(Larp $larp, array $characterIds, array $tags): array
    {
        // Placeholder - to be implemented
        return [
            'larp_title' => $larp->getTitle(),
            'larp_description' => $larp->getDescription(),
            // @TODO: Add character data
            // @TODO: Add existing thread data
            // @TODO: Add tag data
        ];
    }

    /**
     * @TODO Phase 1: Implement prompt template
     * @TODO Phase 2: Load from configuration
     */
    private function buildPrompt(Larp $larp, array $context, array $tags): string
    {
        // Placeholder - to be implemented
        return sprintf(
            'Generate 3 story thread suggestions for LARP "%s". Response must be valid JSON.',
            $larp->getTitle()
        );
    }

    /**
     * @return ThreadSuggestionDTO[]
     */
    private function parseResponse(string $content): array
    {
        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['suggestions']) || !is_array($data['suggestions'])) {
                throw new \RuntimeException('Invalid response format: missing suggestions array');
            }

            return array_map(
                fn (array $item) => ThreadSuggestionDTO::fromArray($item),
                $data['suggestions']
            );
        } catch (\JsonException $e) {
            throw new \RuntimeException('Failed to parse AI response: ' . $e->getMessage(), 0, $e);
        }
    }
}
