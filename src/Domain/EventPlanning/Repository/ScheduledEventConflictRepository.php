<?php

namespace App\Domain\EventPlanning\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\EventPlanning\Entity\ScheduledEvent;
use App\Domain\EventPlanning\Entity\ScheduledEventConflict;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<ScheduledEventConflict>
 */
class ScheduledEventConflictRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduledEventConflict::class);
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
