<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\DTO;

/**
 * DTO for a single thread suggestion from AI.
 */
final readonly class ThreadSuggestionDTO
{
    /**
     * @param int[] $characterIds
     * @param string[] $tags
     */
    public function __construct(
        public string $title,
        public string $summary,
        public array $acts,
        public array $characterIds,
        public array $tags,
        public string $rationale, // Why AI suggested this
    ) {
    }

    /**
     * Create from AI response array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? '',
            summary: $data['summary'] ?? '',
            acts: $data['acts'] ?? [],
            characterIds: $data['characters'] ?? [],
            tags: $data['tags'] ?? [],
            rationale: $data['rationale'] ?? '',
        );
    }
}
