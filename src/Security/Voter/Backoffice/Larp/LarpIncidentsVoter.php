<?php

namespace App\Security\Voter\Backoffice\Larp;

use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpIncidentsVoter extends Voter
{
    public const LIST_VIEW = 'VIEW_BO_LARP_INCIDENTS';
    public const VIEW = 'VIEW_BO_LARP_INCIDENT';

    protected function supports(string $attribute, $subject): bool
    {
        return (($attribute === self::LIST_VIEW ||
                $attribute === self::VIEW)
            && $subject instanceof Larp);
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
            return $participant->getUser()->getId() === $user->getId() &&
                ($participant->isAdmin() || $participant->isTrustPerson());
        })->first();

        if (!$userOrganizer) {
            return false;
        }

        return true;
    }
}
