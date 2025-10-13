<?php

namespace App\Domain\Core\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpDetailsVoter extends Voter
{
    public const VIEW = 'VIEW_BO_LARP_DETAILS';

    protected function supports(string $attribute, $subject): bool
    {
        // This voter only supports the VIEW attribute and Core objects
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
        /** @var \App\Domain\Core\Controller\Backoffice\\App\Domain\Core\Entity\LarpParticipant|null $userOrganizer */
        $userOrganizer = $participants->filter(fn (LarpParticipant $participant): bool => $participant->getUser()->getId() === $user->getId() && $participant->isOrganizer())->first();
        // If the user is not participating as an organizer for this LARP, deny.
        return $userOrganizer !== null;
    }
}
