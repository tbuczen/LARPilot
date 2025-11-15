<?php

namespace App\Domain\Core\Service;

use App\Domain\Account\Entity\Enum\UserStatus;
use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use App\Domain\Core\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;

class LocationApprovalService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Approve a location
     */
    public function approve(Location $location, User $approver): void
    {
        $location->setApprovalStatus(LocationApprovalStatus::APPROVED);
        $location->setApprovedBy($approver);
        $location->setApprovedAt(new \DateTime());
        $location->setRejectionReason(null);

        $this->entityManager->flush();
    }

    /**
     * Reject a location with an optional reason
     */
    public function reject(Location $location, User $rejector, ?string $reason = null): void
    {
        $location->setApprovalStatus(LocationApprovalStatus::REJECTED);
        $location->setApprovedBy($rejector);
        $location->setApprovedAt(new \DateTime());
        $location->setRejectionReason($reason);

        $this->entityManager->flush();
    }

    /**
     * Check if a user can create a location
     * Only users with APPROVED status can create locations
     */
    public function canUserCreateLocation(User $user): bool
    {
        return $user->getStatus() === UserStatus::APPROVED;
    }

    /**
     * Check if a user can edit a location
     * - Super admins can edit any location
     * - Users can edit their own pending or rejected locations
     * - Users cannot edit approved locations (unless super admin)
     */
    public function canUserEditLocation(User $user, Location $location): bool
    {
        // Super admins can edit any location
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Users can only edit locations they created
        if ($location->getCreatedBy() !== $user) {
            return false;
        }

        // Users can edit their own pending or rejected locations
        return $location->isPending() || $location->isRejected();
    }

    /**
     * Determine the initial approval status for a new location
     * - Locations created by SUPER_ADMIN are auto-approved
     * - Locations created by regular users are pending
     */
    public function getInitialApprovalStatus(User $user): LocationApprovalStatus
    {
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return LocationApprovalStatus::APPROVED;
        }

        return LocationApprovalStatus::PENDING;
    }

    /**
     * Auto-approve a location and set metadata
     * Used when SUPER_ADMIN creates a location
     */
    public function autoApprove(Location $location, User $creator): void
    {
        $location->setApprovalStatus(LocationApprovalStatus::APPROVED);
        $location->setApprovedBy($creator);
        $location->setApprovedAt(new \DateTime());
    }
}
