<?php

namespace App\Domain\CallTranscript\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CallTranscriptVoter extends Voter
{
    public const MANAGE = 'CALL_TRANSCRIPT_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::MANAGE && $subject instanceof Larp;
    }

    /**
     * @param Larp $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$user->isApproved()) {
            return false;
        }

        foreach ($subject->getParticipants() as $participant) {
            $participantUser = $participant->getUser();
            if ($participantUser instanceof User && $participantUser->getId()->equals($user->getId()) && $participant->isAdmin()) {
                return true;
            }
        }

        return false;
    }
}
