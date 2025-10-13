<?php

namespace App\Domain\EventPlanning\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\EventPlanning\Entity\PlanningResource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<\App\Domain\EventPlanning\Entity\PlanningResource>
 */
class PlanningResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningResource::class);
    }

    public function save(PlanningResource $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PlanningResource $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
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
