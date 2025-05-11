<?php

namespace App\Repository\StoryObject;

use App\Entity\StoryObject\StoryObject;
use App\Repository\BaseRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoryObject>
 *
 * @method null|StoryObject find($id, $lockMode = null, $lockVersion = null)
 * @method null|StoryObject findOneBy(array $criteria, array $orderBy = null)
 * @method StoryObject[]    findAll()
 * @method StoryObject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoryObjectRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoryObject::class);
    }

}
