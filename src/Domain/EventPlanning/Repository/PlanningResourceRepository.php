<?php

namespace App\Domain\EventPlanning\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\EventPlanning\Entity\PlanningResource;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<PlanningResource>
 */
class PlanningResourceRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningResource::class);
    }

    /**
     * Find all resources for a specific LARP.
     *
     * @return PlanningResource[]
     */
    public function findByLarp(Larp $larp): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('r.type', 'ASC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find resources available during a specific time period.
     *
     * @return PlanningResource[]
     */
    public function findAvailableDuring(Larp $larp, \DateTimeInterface $startTime, \DateTimeInterface $endTime): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.larp = :larp')
            ->andWhere('(r.availableFrom IS NULL OR r.availableFrom <= :startTime)')
            ->andWhere('(r.availableUntil IS NULL OR r.availableUntil >= :endTime)')
            ->setParameter('larp', $larp)
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
