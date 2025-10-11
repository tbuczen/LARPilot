<?php

namespace App\Repository;

use App\Entity\GameMap;
use App\Entity\Larp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameMap>
 */
class GameMapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameMap::class);
    }

    public function save(GameMap $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GameMap $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all maps for a specific LARP.
     *
     * @return GameMap[]
     */
    public function findByLarp(Larp $larp): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('gm.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
