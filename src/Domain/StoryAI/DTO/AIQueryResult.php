<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\DTO;

/**
 * Represents the result of an AI query including response and metadata.
 */
final readonly class AIQueryResult
{
    /**
     * @param SearchResult[] $sources
     * @param array{prompt_tokens: int, completion_tokens: int, total_tokens: int} $usage
     */
    public function __construct(
        public string $response,
        public array $sources,
        public array $usage,
        public string $model,
        public float $processingTime,
    ) {
    }

    /**
     * Get source titles for attribution.
     *
     * @return string[]
     */
    public function getSourceTitles(): array
    {
        return array_map(fn (SearchResult $r) => $r->title, $this->sources);
    }

    /**
     * Get estimated cost in USD (estimate).
     */
    public function getEstimatedCost(): float
    {
        // GPT-4o-mini pricing: $0.15/1M input, $0.60/1M output
        $inputCost = ($this->usage['prompt_tokens'] / 1_000_000) * 0.15;
        $outputCost = ($this->usage['completion_tokens'] / 1_000_000) * 0.60;

        return $inputCost + $outputCost;
    }
}
