<?php

namespace App\Domain\Core\UseCase\GenerateInvitation;

use App\Domain\Core\Entity\Enum\ParticipantRole;

readonly class GenerateInvitationCommand
{
    public function __construct(
        public string    $larpId,
        public \DateTimeImmutable $validTo,
        public ParticipantRole $invitedRole = ParticipantRole::STAFF
    ) {
    }
}
