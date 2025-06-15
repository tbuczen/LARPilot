<?php

namespace App\Entity\Enum;

enum LarpIncidentStatus: string
{
    case NEW = 'NEW';
    case IN_PROGRESS = 'IN_PROGRESS';
    case FEEDBACK_GIVEN = 'FEEDBACK_GIVEN';
    case CLOSED = 'CLOSED';
}
