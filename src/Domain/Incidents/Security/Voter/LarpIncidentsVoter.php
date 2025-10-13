<?php

namespace App\Domain\Incidents\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
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
        $userOrganizer = $participants->filter(fn (LarpParticipant $participant): bool => $participant->getUser()->getId() === $user->getId() &&
            ($participant->isAdmin() || $participant->isTrustPerson()))->first();
        return $userOrganizer !== null;
    }
}
