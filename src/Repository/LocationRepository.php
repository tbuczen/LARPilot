<?php

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Location>
 */
class LocationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    public function findActiveAndPublicForUser(UserInterface $user): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('l.title', 'ASC');

        $qb->where(
            $qb->expr()->orX(
                $qb->expr()->eq('l.isPublic', true),
                $qb->expr()->eq('l.createdBy', $user)
            )
        );

        return $qb->getQuery()
            ->getResult();
    }

    public function findByLocation(string $search): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.isActive = :active')
            ->andWhere('l.title LIKE :search OR l.city LIKE :search OR l.country LIKE :search OR l.address LIKE :search')
            ->setParameter('active', true)
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('l.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithCoordinates(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.isActive = :active')
            ->andWhere('l.latitude IS NOT NULL AND l.longitude IS NOT NULL')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }
}
