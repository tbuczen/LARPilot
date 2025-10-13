<?php

namespace App\Domain\Application\DTO;

readonly class AllocationSuggestionDTO
{
    public function __construct(
        public string $applicationId,
        public string $applicantEmail,
        public string $applicantUserId,
        public string $characterId,
        public string $characterTitle,
        public string $choiceId,
        public int $priority,
        public int $voteScore,
        public float $totalScore,
        public ?string $justification = null,
    ) {
    }
}
