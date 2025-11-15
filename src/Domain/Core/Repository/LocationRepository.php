<?php

namespace App\Domain\Core\Repository;

use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use App\Domain\Core\Entity\Location;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends BaseRepository<Location>
 */
class LocationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    /**
     * Find active locations that are:
     * - Public AND approved
     * - OR created by the user (any status)
     */
    public function findActiveAndPublicForUser(UserInterface $user): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('l.title', 'ASC');

        $qb->andWhere(
            $qb->expr()->orX(
                // Public locations that are approved
                $qb->expr()->andX(
                    $qb->expr()->eq('l.isPublic', ':public'),
                    $qb->expr()->eq('l.approvalStatus', ':approved')
                ),
                // OR locations created by this user (any status)
                $qb->expr()->eq('l.createdBy', ':user')
            )
        )
            ->setParameter('public', true)
            ->setParameter('approved', LocationApprovalStatus::APPROVED)
            ->setParameter('user', $user);

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Search for approved locations by title, city, country, or address
     */
    public function findByLocation(string $search): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.isActive = :active')
            ->andWhere('l.approvalStatus = :approved')
            ->andWhere('l.title LIKE :search OR l.city LIKE :search OR l.country LIKE :search OR l.address LIKE :search')
            ->setParameter('active', true)
            ->setParameter('approved', LocationApprovalStatus::APPROVED)
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('l.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find approved locations with coordinates (for map display)
     */
    public function findWithCoordinates(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.isActive = :active')
            ->andWhere('l.approvalStatus = :approved')
            ->andWhere('l.latitude IS NOT NULL AND l.longitude IS NOT NULL')
            ->setParameter('active', true)
            ->setParameter('approved', LocationApprovalStatus::APPROVED)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all pending locations (for super admin review)
     */
    public function findPendingLocations(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.approvalStatus = :pending')
            ->setParameter('pending', LocationApprovalStatus::PENDING)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all approved locations
     */
    public function findApprovedLocations(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.approvalStatus = :approved')
            ->setParameter('approved', LocationApprovalStatus::APPROVED)
            ->orderBy('l.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find locations by approval status
     */
    public function findByApprovalStatus(LocationApprovalStatus $status): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.approvalStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find approved, active, and public locations (for public list)
     */
    public function findPublicLocations(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.isActive = :active')
            ->andWhere('l.isPublic = :public')
            ->andWhere('l.approvalStatus = :approved')
            ->setParameter('active', true)
            ->setParameter('public', true)
            ->setParameter('approved', LocationApprovalStatus::APPROVED)
            ->orderBy('l.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
