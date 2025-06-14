<?php

namespace App\Security\Voter\Backoffice\Larp;

use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpIntegrationSettingsVoter extends Voter
{
    public const MODIFY = 'VIEW_BO_LARP_INTEGRATION_SETTINGS';

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::MODIFY && $subject instanceof Larp;
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
            return $participant->getUser()->getId() === $user->getId() && $participant->isAdmin();
        })->first();

        if (!$userOrganizer) {
            return false;
        }

        return true;
    }
}
