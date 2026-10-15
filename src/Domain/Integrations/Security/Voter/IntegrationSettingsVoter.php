<?php

namespace App\Domain\Integrations\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class IntegrationSettingsVoter extends Voter
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

        if (!$user->isApproved()) {
            return false;
        }

        foreach ($subject->getParticipants() as $participant) {
            $participantUser = $participant->getUser();
            if (!$participantUser instanceof User) {
                continue;
            }

            if ($participantUser->getId()->equals($user->getId()) && $participant->isAdmin()) {
                return true;
            }
        }

        return false;
    }
}
