<?php

namespace App\Domain\Application\Entity\Enum;

enum SubmissionStatus: string
{
    case NEW = 'new';
    case CONSIDER = 'consider';
    case REJECTED = 'rejected';
    case ACCEPTED = 'accepted';
    case OFFERED = 'offered';      // Character assigned by organizer, awaiting player confirmation
    case CONFIRMED = 'confirmed';  // Player confirmed the character assignment
    case DECLINED = 'declined';    // Player declined the character assignment
}
