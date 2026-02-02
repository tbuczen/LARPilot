<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\Query;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\DTO\SearchResult;

/**
 * Builds context for LLM prompts from search results.
 *
 * Note: Lore document support has been removed pending migration to Supabase.
 * Once lore documents are stored in Supabase, this class can query them
 * via VectorStoreInterface.
 */
class ContextBuilder
{
    // Reserve tokens for system prompt and response
    private const RESERVED_TOKENS = 4000;
    // Approximate characters per token
    private const CHARS_PER_TOKEN = 4;

    /**
     * Build context string from search results.
     *
     * @param SearchResult[] $searchResults
     * @param int $maxTokens Maximum tokens for context
     */
    public function buildContext(
        array $searchResults,
        Larp $larp,
        int $maxTokens = 12000
    ): string {
        $availableChars = ($maxTokens - self::RESERVED_TOKENS) * self::CHARS_PER_TOKEN;
        $context = [];
        $usedChars = 0;

        // Add search results
        foreach ($searchResults as $result) {
            $resultContext = $this->formatSearchResult($result);
            $resultChars = strlen($resultContext);

            if ($usedChars + $resultChars <= $availableChars) {
                $context[] = $resultContext;
                $usedChars += $resultChars;
            } else {
                // Try to add a truncated version
                $remainingChars = $availableChars - $usedChars - 100; // Leave buffer
                if ($remainingChars > 200) {
                    $truncated = $this->formatSearchResultTruncated($result, $remainingChars);
                    $context[] = $truncated;
                }
                break;
            }
        }

        return implode("\n\n---\n\n", $context);
    }

    /**
     * Build a system prompt for story assistance.
     */
    public function buildSystemPrompt(Larp $larp): string
    {
        return <<<PROMPT
You are a creative writing assistant for LARP (Live Action Role-Playing) story development.

You are helping writers develop the story for: "{$larp->getTitle()}"

Your role is to:
- Help writers find connections between characters and plot elements
- Suggest story arcs, motivations, and relationships
- Identify potential plot holes or inconsistencies
- Provide creative suggestions that fit the established setting
- Maintain consistency with existing lore and character backgrounds

Guidelines:
- Always base your suggestions on the provided context
- If information is missing, acknowledge it and ask for clarification
- Be creative but stay within the established setting
- Consider the interconnected nature of LARP stories where multiple characters interact
- Suggest ideas that create interesting player experiences
- Flag any potential conflicts with existing story elements

The context below contains relevant information from the LARP's story database.
Use this information to provide informed, contextual suggestions.
PROMPT;
    }

    private function formatSearchResult(SearchResult $result): string
    {
        $typeLabel = $result->isStoryObject() ? $result->entityType : 'Lore';

        return <<<CONTENT
[{$typeLabel}] {$result->title}

{$result->content}
CONTENT;
    }

    private function formatSearchResultTruncated(SearchResult $result, int $maxChars): string
    {
        $typeLabel = $result->isStoryObject() ? $result->entityType : 'Lore';
        $headerLength = strlen("[{$typeLabel}] {$result->title}\n\n");
        $contentLength = $maxChars - $headerLength - 3; // -3 for "..."

        $truncatedContent = substr($result->content, 0, max(0, $contentLength)) . '...';

        return <<<CONTENT
[{$typeLabel}] {$result->title}

{$truncatedContent}
CONTENT;
    }
}
