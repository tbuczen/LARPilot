<?php

namespace App\Repository\StoryObject;

use App\Entity\StoryObject\Relation;
use App\Repository\BaseRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Relation>
 *
 * @method null|Relation find($id, $lockMode = null, $lockVersion = null)
 * @method null|Relation findOneBy(array $criteria, array $orderBy = null)
 * @method Relation[]    findAll()
 * @method Relation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RelationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Relation::class);
    }

    /**
     * Find all relations where both source and target are in the given object IDs
     * @param string[] $objectIds
     * @return Relation[]
     */
    public function findRelationsBetweenObjects(array $objectIds): array
    {
        if (empty($objectIds)) {
            return [];
        }

        return $this->createQueryBuilder('r')
            ->join('r.from', 'f')
            ->join('r.to', 't')
            ->where('f.id IN (:ids)')
            ->andWhere('t.id IN (:ids)')
            ->setParameter('ids', $objectIds)
            ->getQuery()
            ->getResult();
    }
}