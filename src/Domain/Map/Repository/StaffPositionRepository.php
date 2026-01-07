<?php

declare(strict_types=1);

namespace App\Domain\Map\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Map\Entity\GameMap;
use App\Domain\Map\Entity\StaffPosition;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<StaffPosition>
 */
class StaffPositionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StaffPosition::class);
    }

    /**
     * Find all staff positions for a specific map.
     *
     * @return StaffPosition[]
     */
    public function findByMap(GameMap $map): array
    {
        return $this->createQueryBuilder('sp')
            ->join('sp.participant', 'p')
            ->where('sp.map = :map')
            ->setParameter('map', $map)
            ->orderBy('sp.positionUpdatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all staff positions for a specific LARP.
     *
     * @return StaffPosition[]
     */
    public function findByLarp(Larp $larp): array
    {
        return $this->createQueryBuilder('sp')
            ->join('sp.map', 'm')
            ->where('m.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('sp.positionUpdatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find position for a specific participant on a specific map.
     */
    public function findByParticipantAndMap(LarpParticipant $participant, GameMap $map): ?StaffPosition
    {
        return $this->createQueryBuilder('sp')
            ->where('sp.participant = :participant')
            ->andWhere('sp.map = :map')
            ->setParameter('participant', $participant)
            ->setParameter('map', $map)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find or create a position for a participant on a map.
     */
    public function findOrCreate(LarpParticipant $participant, GameMap $map): StaffPosition
    {
        $position = $this->findByParticipantAndMap($participant, $map);

        if (!$position) {
            $position = new StaffPosition();
            $position->setParticipant($participant);
            $position->setMap($map);
        }

        return $position;
    }
}
