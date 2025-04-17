<?php

namespace App\Domain\Larp\UseCase\GenerateInvitation;

use App\Entity\Enum\UserRole;

readonly class GenerateInvitationCommand
{
    public function __construct(
        public string    $larpId,
        public \DateTimeImmutable $validTo,
        public UserRole $invitedRole = UserRole::STAFF
    )
    {
    }
}
