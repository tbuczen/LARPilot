<?php

namespace App\Entity\Enum;

enum RecruitmentProposalStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
}
