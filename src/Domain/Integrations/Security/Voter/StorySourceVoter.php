<?php

namespace App\Domain\Integrations\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\StoryObject\Entity\StoryObject;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class StorySourceVoter extends Voter
{
    public const LINK = 'STORY_SOURCE_LINK';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::LINK && $subject instanceof StoryObject;
    }

    /**
     * @param StoryObject $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$user->isApproved()) {
            return false;
        }

        $larp = $subject->getLarp();
        if ($larp === null) {
            return false;
        }

        foreach ($larp->getParticipants() as $participant) {
            $participantUser = $participant->getUser();
            if (!$participantUser instanceof User || !$participantUser->getId()->equals($user->getId())) {
                continue;
            }

            if ($participant->isAdmin() || $participant->isStoryWriter()) {
                return true;
            }
        }

        return false;
    }
}
