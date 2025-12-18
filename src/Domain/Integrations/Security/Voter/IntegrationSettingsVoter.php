<?php

namespace App\Domain\Integrations\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
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

        $participants = $subject->getParticipants();
        /** @var LarpParticipant|null $userOrganizer */
        $userOrganizer = $participants->filter(fn (LarpParticipant $participant): bool => $participant->getUser()->getId() === $user->getId() && $participant->isAdmin())->first();
        return $userOrganizer !== null;
    }
}
