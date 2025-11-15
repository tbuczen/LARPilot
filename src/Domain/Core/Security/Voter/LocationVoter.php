<?php

namespace App\Domain\Core\Security\Voter;

use App\Domain\Account\Entity\Enum\UserStatus;
use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Entity\Location;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LocationVoter extends Voter
{
    // LARP location management (existing)
    public const MANAGE = 'MANAGE_LARP_LOCATION';

    // Location approval system permissions (new)
    public const CREATE = 'CREATE_LOCATION';
    public const EDIT = 'EDIT_LOCATION';
    public const APPROVE = 'APPROVE_LOCATION';
    public const REJECT = 'REJECT_LOCATION';
    public const DELETE = 'DELETE_LOCATION';

    protected function supports(string $attribute, $subject): bool
    {
        // Support LARP location management (existing)
        if ($attribute === self::MANAGE && $subject instanceof Larp) {
            return true;
        }

        // Support location approval permissions (new)
        $approvalAttributes = [self::CREATE, self::EDIT, self::APPROVE, self::REJECT, self::DELETE];
        if (in_array($attribute, $approvalAttributes, true)) {
            // CREATE doesn't need a subject
            if ($attribute === self::CREATE) {
                return true;
            }
            // Other attributes require a Location subject
            return $subject instanceof Location;
        }

        return false;
    }

    /**
     * @param Larp|Location|null $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::MANAGE => $this->canManageLarpLocation($subject, $user),
            self::CREATE => $this->canCreateLocation($user),
            self::EDIT => $this->canEditLocation($subject, $user),
            self::APPROVE => $this->canApproveLocation($user),
            self::REJECT => $this->canRejectLocation($user),
            self::DELETE => $this->canDeleteLocation($subject, $user),
            default => false,
        };
    }

    /**
     * Existing LARP location management logic
     */
    private function canManageLarpLocation(Larp $larp, User $user): bool
    {
        $participants = $larp->getParticipants();
        /** @var LarpParticipant|null $userOrganizer */
        $userOrganizer = $participants->filter(
            fn (LarpParticipant $participant): bool =>
                $participant->getUser()->getId() === $user->getId() && $participant->isAdmin()
        )->first();

        // If the user is not participating as an organizer for this LARP, deny.
        if (!$userOrganizer) {
            return false;
        }

        $location = $larp->getLocation();
        if ($location === null) {
            return true;
        }

        return $location->getCreatedBy() === $user;
    }

    /**
     * Only users with APPROVED status can create locations
     */
    private function canCreateLocation(User $user): bool
    {
        return $user->getStatus() === UserStatus::APPROVED;
    }

    /**
     * Users can edit:
     * - Their own pending or rejected locations
     * - Super admins can edit any location
     */
    private function canEditLocation(Location $location, User $user): bool
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
     * Only super admins can approve locations
     */
    private function canApproveLocation(User $user): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }

    /**
     * Only super admins can reject locations
     */
    private function canRejectLocation(User $user): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }

    /**
     * Users can delete:
     * - Their own pending or rejected locations
     * - Super admins can delete any location
     */
    private function canDeleteLocation(Location $location, User $user): bool
    {
        // Super admins can delete any location
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Users can only delete locations they created
        if ($location->getCreatedBy() !== $user) {
            return false;
        }

        // Users can delete their own pending or rejected locations
        return $location->isPending() || $location->isRejected();
    }
}
