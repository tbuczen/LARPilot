<?php

namespace App\Repository\StoryObject;

use App\Entity\StoryObject\StoryRecruitment;
use App\Repository\BaseRepository;
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
