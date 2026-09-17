<?php

namespace App\Domain\Core\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpViewVoter extends Voter
{
    public const VIEW = 'LARP_VIEW';

    protected function supports(string $attribute, $subject): bool
    {
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
        /** @var LarpParticipant|false $participant */
        $participant = $participants->filter(
            fn (LarpParticipant $participant): bool => $participant->getUser()->getId() === $user->getId()
        )->first();

        return $participant instanceof LarpParticipant;
    }
}
