<?php

namespace App\Domain\StoryObject\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\StoryObject\Entity\Thread;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<\App\Domain\StoryObject\Entity\Thread>
 *
 * @method null|\App\Domain\StoryObject\Entity\Thread find($id, $lockMode = null, $lockVersion = null)
 * @method null|\App\Domain\StoryObject\Entity\Thread findOneBy(array $criteria, array $orderBy = null)
 * @method \App\Domain\StoryObject\Entity\Thread[]    findAll()
 * @method Thread[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ThreadRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Thread::class);
    }
}
