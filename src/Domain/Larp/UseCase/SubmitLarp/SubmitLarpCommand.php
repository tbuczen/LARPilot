<?php

namespace App\Domain\Larp\UseCase\SubmitLarp;

readonly class SubmitLarpCommand
{
    public function __construct(
        public string     $name,
        public string     $description,
        public string     $submittedByUserId,
        public ?string    $location = null,
        public ?\DateTime $startDate = null,
        public ?\DateTime $endDate = null,
    ) {
    }
}
