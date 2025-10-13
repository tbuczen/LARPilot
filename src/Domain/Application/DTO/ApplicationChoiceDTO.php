<?php

namespace App\Domain\Application\DTO;

readonly class ApplicationChoiceDTO
{
    public function __construct(
        public string $id,
        public string $characterId,
        public string $characterTitle,
        public string $applicationId,
        public string $applicantEmail,
        public int $priority,
        public ?string $justification,
        public ?string $visual,
        public int $voteScore,
        public VoteStatsDTO $voteStats,
    ) {
    }
}
