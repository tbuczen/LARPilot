<?php

namespace App\Domain\Map\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Map\Entity\GameMap;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<GameMap>
 */
class GameMapRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameMap::class);
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
