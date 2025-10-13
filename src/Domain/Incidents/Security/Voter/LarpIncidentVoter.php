<?php

namespace App\Domain\Incidents\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpIncidentVoter extends Voter
{
    public const VIEW = 'VIEW_BO_LARP_INCIDENT';

    protected function supports(string $attribute, $subject): bool
    {
        return ($attribute === self::VIEW && $subject instanceof Larp);
    }

    /**
     * @param Larp $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        //        $participants = $subject->getParticipants();
        //        /** @var LarpParticipant|null $userOrganizer */
        //        $userOrganizer = $participants->filter(function (LarpParticipant $participant) use ($user) {
        //            return $participant->getUser()->getId() === $user->getId() && $participant->isAdmin();
        //        })->first();
        //
        //        if (!$userOrganizer) {
        //            return false;
        //        }
        return $user instanceof User;
    }
}
