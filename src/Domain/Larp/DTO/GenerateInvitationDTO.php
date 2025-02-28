<?php

namespace App\Domain\Larp\DTO;

readonly class GenerateInvitationDTO
{
    public function __construct(
        public string $invitationCode,
        public string $larpId,
        public string $validTo,
        public string $invitedRole
    ) {}
}
