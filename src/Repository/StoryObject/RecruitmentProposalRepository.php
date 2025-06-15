<?php

namespace App\Repository\StoryObject;

use App\Entity\StoryObject\RecruitmentProposal;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<RecruitmentProposal>
 */
class RecruitmentProposalRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecruitmentProposal::class);
    }
}
