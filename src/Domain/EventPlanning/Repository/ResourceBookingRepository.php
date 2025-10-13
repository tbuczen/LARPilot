<?php

namespace App\Domain\EventPlanning\Repository;

use App\Domain\EventPlanning\Entity\PlanningResource;
use App\Domain\EventPlanning\Entity\ResourceBooking;
use App\Domain\EventPlanning\Entity\ScheduledEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResourceBooking>
 */
class ResourceBookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResourceBooking::class);
    }

    public function save(ResourceBooking $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ResourceBooking $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
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
