<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service;

use App\Domain\StoryAI\DTO\AIRequestDTO;
use App\Domain\StoryAI\DTO\AIResponseDTO;

/**
 * Common interface for all AI providers (OpenAI, Claude, Ollama, etc.).
 *
 * Keeps provider implementations swappable and testable.
 */
interface AIProviderInterface
{
    /**
     * Send a prompt to the AI provider and get structured response.
     *
     * @throws \RuntimeException if API call fails
     */
    public function generate(AIRequestDTO $request): AIResponseDTO;

    /**
     * Get the provider name (e.g., 'openai', 'claude', 'ollama').
     */
    public function getName(): string;

    /**
     * Check if the provider is configured and available.
     */
    public function isAvailable(): bool;

    /**
     * Estimate cost for a request (in USD cents).
     * Returns 0 for free providers.
     *
     * @TODO: Implement in Phase 5 for cost tracking
     */
    public function estimateCost(AIRequestDTO $request): int;
}
