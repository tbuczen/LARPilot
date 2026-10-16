<?php

namespace App\Domain\Core\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Service\Workflow\LarpWorkflowService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpCancelVoter extends Voter
{
    public const CANCEL = 'CANCEL_LARP';

    public function __construct(
        private readonly LarpWorkflowService $workflowService,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::CANCEL && $subject instanceof Larp;
    }

    /**
     * @param Larp $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$user->isApproved()) {
            return false;
        }

        $isOrganizer = $subject->getParticipants()->exists(
            fn (int $key, LarpParticipant $participant): bool => $participant->getUser()->getId()->equals($user->getId()) && $participant->isAdmin()
        );
        if (!$isOrganizer) {
            return false;
        }

        return $this->workflowService->canTransition($subject, LarpWorkflowService::CANCEL_TRANSITION);
    }
}
