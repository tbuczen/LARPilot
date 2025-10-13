<?php

namespace App\Domain\StoryObject\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpStoryVoter extends Voter
{
    public const VIEW = 'VIEW_BO_LARP_STORY';
    public const MODIFY = 'VIEW_BO_LARP_STORY_MODIFY';

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
        /** @var LarpParticipant|null $userOrganizer */
        $userOrganizer = $participants->filter(fn (LarpParticipant $participant): bool => $participant->getUser()->getId() === $user->getId() &&
            (
                $participant->isOrganizer() ||
                $participant->isStoryWriter()
            ))->first();
        return $userOrganizer !== null;
    }
}
