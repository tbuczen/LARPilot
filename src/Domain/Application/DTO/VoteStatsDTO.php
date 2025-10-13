<?php

namespace App\Domain\Application\DTO;

readonly class VoteStatsDTO
{
    /**
     * @param VoteDetailDTO[] $details
     */
    public function __construct(
        public int $upvotes,
        public int $downvotes,
        public int $total,
        public array $details,
    ) {
    }
}
