<?php

namespace App\Domain\StoryMarketplace\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\StoryMarketplace\Entity\StoryRecruitment;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<StoryRecruitment>
 */
class StoryRecruitmentRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoryRecruitment::class);
    }
}
