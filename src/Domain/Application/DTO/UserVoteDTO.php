<?php

namespace App\Domain\Application\DTO;

readonly class UserVoteDTO
{
    public function __construct(
        public string $choiceId,
        public int $vote,
        public ?string $justification,
    ) {
    }
}
