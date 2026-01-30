<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\Query;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\DTO\AIQueryResult;
use App\Domain\StoryAI\DTO\ChatMessage;
use App\Domain\StoryAI\DTO\SearchResult;
use App\Domain\StoryAI\Service\Provider\LLMProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Main RAG (Retrieval-Augmented Generation) query orchestrator.
 *
 * Combines vector search with LLM completion to provide contextual AI assistance.
 */
readonly class RAGQueryService
{
    public function __construct(
        private VectorSearchService  $vectorSearchService,
        private ContextBuilder       $contextBuilder,
        private LLMProviderInterface $llmProvider,
        private ?LoggerInterface     $logger = null,
    ) {
    }

    /**
     * Execute a RAG query against the LARP's story content.
     *
     * @param array<ChatMessage> $conversationHistory Previous messages in the conversation
     */
    public function query(
        string $userQuery,
        Larp $larp,
        array $conversationHistory = [],
        int $maxSources = 10,
        float $minSimilarity = 0.4,
    ): AIQueryResult {
        $startTime = microtime(true);

        $this->logger?->info('Processing RAG query', [
            'larp_id' => $larp->getId()->toRfc4122(),
            'query_length' => strlen($userQuery),
        ]);

        // Step 1: Search for relevant content
        $searchResults = $this->vectorSearchService->searchByQuery(
            $userQuery,
            $larp,
            $maxSources,
            $minSimilarity
        );

        $this->logger?->debug('Search results found', [
            'count' => count($searchResults),
        ]);

        // Step 2: Build context from search results
        $context = $this->contextBuilder->buildContext($searchResults, $larp);

        // Step 3: Build system prompt
        $systemPrompt = $this->contextBuilder->buildSystemPrompt($larp);

        // Step 4: Compose messages for LLM
        $messages = $this->composeMessages(
            $systemPrompt,
            $context,
            $userQuery,
            $conversationHistory
        );

        // Step 5: Get completion from LLM
        $completionResult = $this->llmProvider->completeWithMetadata($messages, [
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ]);

        $processingTime = microtime(true) - $startTime;

        $this->logger?->info('RAG query completed', [
            'processing_time' => $processingTime,
            'sources_used' => count($searchResults),
            'tokens' => $completionResult['usage'],
        ]);

        return new AIQueryResult(
            response: $completionResult['content'],
            sources: $searchResults,
            usage: $completionResult['usage'],
            model: $this->llmProvider->getModelName(),
            processingTime: $processingTime,
        );
    }

    /**
     * Execute a simple search query (no LLM, just vector search).
     *
     * @return SearchResult[]
     */
    public function search(
        string $query,
        Larp $larp,
        int $limit = 10,
        float $minSimilarity = 0.4,
    ): array {
        return $this->vectorSearchService->searchByQuery(
            $query,
            $larp,
            $limit,
            $minSimilarity
        );
    }

    /**
     * Get suggestions for a specific story element.
     */
    public function suggestStoryArc(
        string $elementType,
        string $elementTitle,
        string $elementContext,
        Larp $larp,
    ): AIQueryResult {
        $query = sprintf(
            'Suggest a story arc for the %s named "%s". Consider their current situation: %s',
            $elementType,
            $elementTitle,
            $elementContext
        );

        return $this->query($query, $larp);
    }

    /**
     * Analyze potential plot holes or inconsistencies.
     */
    public function analyzePlotConsistency(
        string $plotElement,
        Larp $larp,
    ): AIQueryResult {
        $query = sprintf(
            'Analyze this plot element for potential inconsistencies or plot holes with the established story: %s. Identify any conflicts with existing characters, factions, or established lore.',
            $plotElement
        );

        return $this->query($query, $larp, [], 15, 0.3);
    }

    /**
     * Suggest relationships for a character.
     */
    public function suggestRelationships(
        string $characterName,
        string $characterContext,
        Larp $larp,
    ): AIQueryResult {
        $query = sprintf(
            'Suggest potential relationships for the character "%s" based on their background: %s. Consider existing factions, other characters, and story threads. Suggest both allies and potential enemies or rivals.',
            $characterName,
            $characterContext
        );

        return $this->query($query, $larp);
    }

    /**
     * Find connections between multiple story elements.
     */
    public function findConnections(
        array $elementNames,
        Larp $larp,
    ): AIQueryResult {
        $elements = implode('", "', $elementNames);
        $query = sprintf(
            'Find or suggest connections between these story elements: "%s". How might they be related? What hidden plots could connect them?',
            $elements
        );

        return $this->query($query, $larp, [], 15, 0.3);
    }

    /**
     * Compose the full message array for the LLM.
     *
     * @param ChatMessage[] $conversationHistory
     * @return ChatMessage[]
     */
    private function composeMessages(
        string $systemPrompt,
        string $context,
        string $userQuery,
        array $conversationHistory
    ): array {
        $messages = [];

        // System message with context
        $fullSystemPrompt = $systemPrompt . "\n\n## Relevant Context\n\n" . $context;
        $messages[] = ChatMessage::system($fullSystemPrompt);

        // Add conversation history (if any)
        foreach ($conversationHistory as $message) {
            $messages[] = $message;
        }

        // Add current user query
        $messages[] = ChatMessage::user($userQuery);

        return $messages;
    }
}
