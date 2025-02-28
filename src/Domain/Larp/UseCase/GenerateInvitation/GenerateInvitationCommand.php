<?php

namespace App\Domain\Larp\UseCase\GenerateInvitation;

use App\Enum\UserRole;

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
