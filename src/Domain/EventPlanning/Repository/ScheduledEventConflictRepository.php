<?php

namespace App\Domain\EventPlanning\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\EventPlanning\Entity\ScheduledEvent;
use App\Domain\EventPlanning\Entity\ScheduledEventConflict;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduledEventConflict>
 */
class ScheduledEventConflictRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduledEventConflict::class);
    }

    public function save(ScheduledEventConflict $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ScheduledEventConflict $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all unresolved conflicts for a LARP.
     *
     * @return ScheduledEventConflict[]
     */
    public function findUnresolvedByLarp(Larp $larp): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.event1', 'e1')
            ->where('e1.larp = :larp')
            ->andWhere('c.resolved = false')
            ->setParameter('larp', $larp)
            ->orderBy('c.severity', 'DESC')
            ->addOrderBy('c.detectedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find conflicts for a specific event.
     *
     * @return ScheduledEventConflict[]
     */
    public function findByEvent(ScheduledEvent $event): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.event1 = :event OR c.event2 = :event')
            ->setParameter('event', $event)
            ->orderBy('c.severity', 'DESC')
            ->addOrderBy('c.resolved', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
