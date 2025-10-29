<?php

namespace App\Domain\Map\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Map\Entity\GameMap;
use App\Domain\Map\Entity\MapLocation;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<MapLocation>
 */
class MapLocationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapLocation::class);
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
