<?php

namespace App\Domain\Core\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\LarpParticipantRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpDeleteVoter extends Voter
{
    public const DELETE = 'DELETE_LARP';

    public function __construct(
        private readonly LarpParticipantRepository $participantRepository,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::DELETE && $subject instanceof Larp;
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

        if (!$subject->isDraft()) {
            return false;
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        $participant = $this->participantRepository->findOneBy([
            'larp' => $subject,
            'user' => $user,
        ]);

        if ($participant === null) {
            return false;
        }

        return in_array(ParticipantRole::ORGANIZER, $participant->getRoles(), true);
    }
}
