<?php

namespace App\Domain\Core\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpGeneralSettingsVoter extends Voter
{
    public const MANAGE_LARP_GENERAL_SETTINGS = 'MANAGE_LARP_GENERAL_SETTINGS';

    protected function supports(string $attribute, $subject): bool
    {
        // This voter only supports the MANAGE_LARP_GENERAL_SETTINGS attribute and Core objects
        return $attribute === self::MANAGE_LARP_GENERAL_SETTINGS && $subject instanceof Larp;
    }

    /**
     * @param Larp $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Only approved users can manage LARP settings
        if (!$user->isApproved()) {
            return false;
        }

        // Check if user is an organizer for this specific LARP
        $participants = $subject->getParticipants();
        /** @var LarpParticipant|null $userOrganizer */
        $userOrganizer = $participants->filter(fn (LarpParticipant $participant): bool => $participant->getUser()->getId() === $user->getId() && $participant->isAdmin())->first();
        // If the user is not participating as an organizer for this LARP, deny access
        return $userOrganizer !== null;
    }
}
