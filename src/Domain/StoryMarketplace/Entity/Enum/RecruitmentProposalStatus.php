<?php

namespace App\Domain\StoryMarketplace\Entity\Enum;

enum RecruitmentProposalStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
}
