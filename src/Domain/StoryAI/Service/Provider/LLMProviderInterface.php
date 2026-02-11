<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\Provider;

use App\Domain\StoryAI\DTO\ChatMessage;

/**
 * Interface for LLM completion providers.
 */
interface LLMProviderInterface
{
    /**
     * Generate a completion for the given messages.
     *
     * @param array<ChatMessage> $messages The conversation messages
     * @param array<string, mixed> $options Additional options (temperature, max_tokens, etc.)
     * @return string The completion text
     */
    public function complete(array $messages, array $options = []): string;

    /**
     * Generate a completion and return with metadata.
     *
     * @param array<ChatMessage> $messages The conversation messages
     * @param array<string, mixed> $options Additional options
     * @return array{content: string, usage: array{prompt_tokens: int, completion_tokens: int, total_tokens: int}}
     */
    public function completeWithMetadata(array $messages, array $options = []): array;

    /**
     * Get the model name being used.
     */
    public function getModelName(): string;

    /**
     * Get maximum context length for this model.
     */
    public function getMaxContextLength(): int;

    /**
     * Estimate token count for messages (for context management).
     *
     * @param array<ChatMessage> $messages
     */
    public function estimateMessageTokens(array $messages): int;
}
