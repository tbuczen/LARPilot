<?php

namespace App\Domain\StoryObject\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\StoryObject\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * Create a query builder for events filtered by tags.
     *
     * @param Tag[] $tags
     */
    public function createEventsByTagsQueryBuilder(Larp $larp, array $tags): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e');
        $qb->join('e.tags', 'tag')
            ->where('e.larp = :larp')
            ->andWhere('tag IN (:tags)')
            ->setParameter('larp', $larp)
            ->setParameter('tags', $tags)
            ->groupBy('e.id')
            ->orderBy('COUNT(tag.id)', 'DESC');

        return $qb;
    }
}
