<?php

namespace App\Domain\StoryMarketplace\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\StoryMarketplace\Entity\RecruitmentProposal;
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
