<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Tag>
 */
class TagRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }
}
