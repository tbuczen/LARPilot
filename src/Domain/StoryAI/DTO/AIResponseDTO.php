<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\DTO;

/**
 * Simple DTO for AI provider responses.
 * Normalizes response format across different providers.
 */
final readonly class AIResponseDTO
{
    public function __construct(
        public string $content,
        public int $tokensUsed,
        public int $responseTimeMs,
        public ?array $metadata = null, // Provider-specific metadata
    ) {
    }

    /**
     * Parse JSON response content.
     * Returns null if content is not valid JSON.
     */
    public function getParsedContent(): ?array
    {
        try {
            $decoded = json_decode($this->content, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (\JsonException) {
            return null;
        }
    }
}
