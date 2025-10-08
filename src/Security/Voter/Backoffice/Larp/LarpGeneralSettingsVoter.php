<?php

namespace App\Security\Voter\Backoffice\Larp;

use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpGeneralSettingsVoter extends Voter
{
    public const MANAGE_LARP_GENERAL_SETTINGS = 'MANAGE_LARP_GENERAL_SETTINGS';

    protected function supports(string $attribute, $subject): bool
    {
        // This voter only supports the MANAGE_LARP_GENERAL_SETTINGS attribute and Larp objects
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

        // Check if user is an organizer for this specific LARP
        $participants = $subject->getParticipants();
        /** @var LarpParticipant|null $userOrganizer */
        $userOrganizer = $participants->filter(function (LarpParticipant $participant) use ($user) {
            return $participant->getUser()->getId() === $user->getId() && $participant->isAdmin();
        })->first();

        // If the user is not participating as an organizer for this LARP, deny access
        if (!$userOrganizer) {
            return false;
        }

        // Additional check: only allow if user has specific organizer permissions
        // You might want to add more granular permissions here based on your business logic
        // For example, check if the organizer has specific permissions for general settings
        
        return true;
    }
}
