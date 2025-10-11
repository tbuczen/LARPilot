<?php

namespace App\Repository;

use App\Entity\GameMap;
use App\Entity\MapLocation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MapLocation>
 */
class MapLocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapLocation::class);
    }

    public function save(MapLocation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MapLocation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all locations for a specific map.
     *
     * @return MapLocation[]
     */
    public function findByMap(GameMap $map): array
    {
        return $this->createQueryBuilder('ml')
            ->where('ml.map = :map')
            ->setParameter('map', $map)
            ->orderBy('ml.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
