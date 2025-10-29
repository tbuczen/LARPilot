<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\DTO;

/**
 * Simple DTO for AI provider requests.
 * Keeps provider interface clean and testable.
 */
final readonly class AIRequestDTO
{
    public function __construct(
        public string $prompt,
        public ?string $systemPrompt = null,
        public int $maxTokens = 4096,
        public float $temperature = 0.7,
        public ?array $context = null, // Additional context data
    ) {
    }

    public function getEstimatedInputTokens(): int
    {
        // Rough estimate: ~4 chars per token
        $promptLength = strlen($this->prompt) + strlen($this->systemPrompt ?? '');
        return (int) ceil($promptLength / 4);
    }
}
