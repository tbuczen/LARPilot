<?php

namespace App\Domain\EventPlanning\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\EventPlanning\Entity\ScheduledEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<\App\Domain\EventPlanning\Entity\ScheduledEvent>
 */
class ScheduledEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduledEvent::class);
    }

    public function save(ScheduledEvent $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ScheduledEvent $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all events for a specific LARP.
     *
     * @return ScheduledEvent[]
     */
    public function findByLarp(Larp $larp): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('e.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events within a date range for a LARP.
     *
     * @return ScheduledEvent[]
     */
    public function findByLarpAndDateRange(Larp $larp, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.larp = :larp')
            ->andWhere('e.startTime >= :startDate')
            ->andWhere('e.endTime <= :endDate')
            ->setParameter('larp', $larp)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events that overlap with a given time period.
     *
     * @return ScheduledEvent[]
     */
    public function findOverlapping(\DateTimeInterface $startTime, \DateTimeInterface $endTime, ?ScheduledEvent $excludeEvent = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.startTime < :endTime')
            ->andWhere('e.endTime > :startTime')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        if ($excludeEvent) {
            $qb->andWhere('e.id != :excludeId')
                ->setParameter('excludeId', $excludeEvent->getId());
        }

        return $qb->getQuery()->getResult();
    }
}
