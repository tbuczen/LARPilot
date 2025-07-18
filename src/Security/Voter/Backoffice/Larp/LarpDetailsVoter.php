<?php

namespace App\Security\Voter\Backoffice\Larp;

use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpDetailsVoter extends Voter
{
    public const VIEW = 'VIEW_BO_LARP_DETAILS';

    protected function supports(string $attribute, $subject): bool
    {
        // This voter only supports the VIEW attribute and Larp objects
        return $attribute === self::VIEW && $subject instanceof Larp;
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
        $userOrganizer = $participants->filter(function (LarpParticipant $participant) use ($user) {
            return $participant->getUser()->getId() === $user->getId() && $participant->isOrganizer();
        })->first();
        // If the user is not participating as an organizer for this LARP, deny.
        if (!$userOrganizer) {
            return false;
        }

        return true;
    }
}
