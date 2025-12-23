<?php

namespace App\Domain\Core\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpParticipantsVoter extends Voter
{
    public const VIEW = 'VIEW_BO_LARP_PARTICIPANTS';
    public const DELETE = 'DELETE_LARP_PARTICIPANT';

    protected function supports(string $attribute, $subject): bool
    {
        if ($attribute === self::VIEW) {
            return $subject instanceof Larp;
        }
        
        if ($attribute === self::DELETE) {
            return $subject instanceof LarpParticipant;
        }
        
        return false;
    }

    /**
     * @param Larp|LarpParticipant $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            default => false,
        };
    }

    private function canView(Larp $larp, User $user): bool
    {
        $participants = $larp->getParticipants();
        /** @var LarpParticipant|null $userParticipant */
        $userParticipant = $participants->filter(
            fn (LarpParticipant $participant): bool =>
                $participant->getUser()->getId() === $user->getId() && $participant->isOrganizer()
        )->first();
        
        return $userParticipant !== null;
    }

    private function canDelete(LarpParticipant $participant, User $user): bool
    {
        $larp = $participant->getLarp();
        $participants = $larp->getParticipants();
        
        // Find the current user's participant record
        /** @var LarpParticipant|null $userParticipant */
        $userParticipant = $participants->filter(
            fn (LarpParticipant $p): bool => $p->getUser()->getId() === $user->getId()
        )->first();
        
        // User must be a participant
        if ($userParticipant === null) {
            return false;
        }
        
        // Only organizers can delete participants
        return $userParticipant->isAdmin();
    }
}
