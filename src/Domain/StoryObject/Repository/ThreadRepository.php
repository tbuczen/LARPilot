<?php

namespace App\Domain\StoryObject\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\StoryObject\Entity\Thread;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * Create a query builder for threads filtered by tags.
     *
     * @param Tag[] $tags
     */
    public function createThreadsByTagsQueryBuilder(Larp $larp, array $tags): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t');
        $qb->join('t.tags', 'tag')
            ->where('t.larp = :larp')
            ->andWhere('tag IN (:tags)')
            ->setParameter('larp', $larp)
            ->setParameter('tags', $tags)
            ->groupBy('t.id')
            ->orderBy('COUNT(tag.id)', 'DESC');

        return $qb;
    }
}
