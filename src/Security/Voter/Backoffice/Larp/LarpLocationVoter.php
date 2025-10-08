<?php

namespace App\Security\Voter\Backoffice\Larp;

use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpLocationVoter extends Voter
{
    public const MANAGE = 'MANAGE_LARP_LOCATION';

    protected function supports(string $attribute, $subject): bool
    {
        // This voter only supports the VIEW attribute and Larp objects
        return $attribute === self::MANAGE && $subject instanceof Larp;
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

        $participants = $subject->getParticipants();
        /** @var LarpParticipant|null $userOrganizer */
        $userOrganizer = $participants->filter(fn (LarpParticipant $participant): bool => $participant->getUser()->getId() === $user->getId() && $participant->isAdmin())->first();
        // If the user is not participating as an organizer for this LARP, deny.
        if (!$userOrganizer) {
            return false;
        }

        $location = $subject->getLocation();
        if ($location === null) {
            return true;
        }

        return $location->getCreatedBy() === $userOrganizer;
    }
}
