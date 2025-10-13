<?php

namespace App\Domain\Application\DTO;

readonly class VoteDetailDTO
{
    public function __construct(
        public string $userId,
        public string $username,
        public int $vote,
        public ?string $justification,
        public \DateTimeInterface $createdAt,
    ) {
    }
}
