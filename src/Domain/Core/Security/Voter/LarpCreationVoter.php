<?php

namespace App\Domain\Core\Security\Voter;

use App\Domain\Account\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpCreationVoter extends Voter
{
    public const CREATE = 'CREATE_LARP';

    protected function supports(string $attribute, $subject): bool
    {
        // This voter only supports the CREATE attribute
        // Subject should be null (we're checking general permission)
        return $attribute === self::CREATE;
    }

    /**
     * @param mixed $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Super admins can always create LARPs
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Only approved users can create LARPs
        if (!$user->isApproved()) {
            return false;
        }

        // Get the count of LARPs where user is an organizer
        $currentLarpCount = $user->getOrganizerLarpCount();

        // Check if user can create more LARPs based on their plan
        return $user->canCreateMoreLarps($currentLarpCount);
    }
}
