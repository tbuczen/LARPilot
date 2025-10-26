<?php

namespace App\Domain\StoryObject\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\StoryObject\Entity\Quest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Quest>
 *
 * @method null|Quest find($id, $lockMode = null, $lockVersion = null)
 * @method null|Quest findOneBy(array $criteria, array $orderBy = null)
 * @method Quest[]    findAll()
 * @method Quest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quest::class);
    }

    /**
     * Create a query builder for quests filtered by tags.
     *
     * @param Tag[] $tags
     */
    public function createQuestsByTagsQueryBuilder(Larp $larp, array $tags): QueryBuilder
    {
        $qb = $this->createQueryBuilder('q');
        $qb->join('q.tags', 'tag')
            ->where('q.larp = :larp')
            ->andWhere('tag IN (:tags)')
            ->setParameter('larp', $larp)
            ->setParameter('tags', $tags)
            ->groupBy('q.id')
            ->orderBy('COUNT(tag.id)', 'DESC');

        return $qb;
    }
}
