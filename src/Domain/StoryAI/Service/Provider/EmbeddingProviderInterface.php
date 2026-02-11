<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\Provider;

/**
 * Interface for embedding generation providers.
 */
interface EmbeddingProviderInterface
{
    /**
     * Generate embedding for a single text.
     *
     * @return array<int, float> The embedding vector
     */
    public function embed(string $text): array;

    /**
     * Generate embeddings for multiple texts in a batch.
     *
     * @param array<string> $texts
     * @return array<array<int, float>> Array of embedding vectors
     */
    public function embedBatch(array $texts): array;

    /**
     * Get the model name being used.
     */
    public function getModelName(): string;

    /**
     * Get the dimension count of embeddings produced by this provider.
     */
    public function getDimensions(): int;

    /**
     * Estimate token count for a text (for chunking decisions).
     */
    public function estimateTokenCount(string $text): int;

    /**
     * Get maximum tokens per embedding request.
     */
    public function getMaxTokens(): int;
}
