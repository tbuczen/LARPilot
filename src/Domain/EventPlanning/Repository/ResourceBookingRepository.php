<?php

namespace App\Domain\EventPlanning\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\EventPlanning\Entity\PlanningResource;
use App\Domain\EventPlanning\Entity\ResourceBooking;
use App\Domain\EventPlanning\Entity\ScheduledEvent;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<ResourceBooking>
 */
class ResourceBookingRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResourceBooking::class);
    }

    /**
     * Find overlapping bookings for a resource within a time period.
     *
     * @return ResourceBooking[]
     */
    public function findOverlappingBookings(
        PlanningResource $resource,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?ScheduledEvent $excludeEvent = null
    ): array {
        $qb = $this->createQueryBuilder('b')
            ->join('b.scheduledEvent', 'e')
            ->where('b.resource = :resource')
            ->andWhere('e.startTime < :endTime')
            ->andWhere('e.endTime > :startTime')
            ->setParameter('resource', $resource)
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        if ($excludeEvent) {
            $qb->andWhere('e.id != :excludeId')
                ->setParameter('excludeId', $excludeEvent->getId());
        }

        return $qb->getQuery()->getResult();
    }
}
