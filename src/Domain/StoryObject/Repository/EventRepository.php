<?php

namespace App\Domain\StoryObject\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\StoryObject\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<\App\Domain\StoryObject\Entity\Event>
 *
 * @method null|Event find($id, $lockMode = null, $lockVersion = null)
 * @method null|Event findOneBy(array $criteria, array $orderBy = null)
 * @method \App\Domain\StoryObject\Entity\Event[]    findAll()
 * @method \App\Domain\StoryObject\Entity\Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }
}
