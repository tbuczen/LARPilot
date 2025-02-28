<?php

namespace App\Domain\Larp\DTO;

readonly class SubmitLarpDTO
{
    public function __construct(
        public string $larpId,
        public string $status,
        public string $name,
    ) {}
}
