<?php

declare(strict_types=1);

namespace App\Domain\Map\Security\Voter;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\LarpParticipantRepository;
use App\Domain\Map\Entity\GameMap;
use App\Domain\Map\Service\StaffPositionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Voter for staff position management permissions.
 *
 * Permissions:
 * - UPDATE_POSITION: Only non-player participants can update their position
 * - VIEW_POSITIONS: All participants can view (filtered by visibility rules in service)
 * - VIEW_ALL_POSITIONS: Only organizers/staff can see all positions
 */
class StaffPositionVoter extends Voter
{
    public const UPDATE_POSITION = 'STAFF_POSITION_UPDATE';
    public const VIEW_POSITIONS = 'STAFF_POSITION_VIEW';
    public const VIEW_ALL_POSITIONS = 'STAFF_POSITION_VIEW_ALL';

    public function __construct(
        private LarpParticipantRepository $participantRepository,
        private StaffPositionService $staffPositionService,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        $supportedAttributes = [
            self::UPDATE_POSITION,
            self::VIEW_POSITIONS,
            self::VIEW_ALL_POSITIONS,
        ];

        if (!in_array($attribute, $supportedAttributes, true)) {
            return false;
        }

        // Subject must be either a Larp or GameMap
        return $subject instanceof Larp || $subject instanceof GameMap;
    }

    /**
     * @param Larp|GameMap $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Get the LARP from the subject
        $larp = $subject instanceof Larp ? $subject : $subject->getLarp();

        // Get participant for this user in this LARP
        $participant = $this->participantRepository->findOneBy([
            'user' => $user,
            'larp' => $larp,
        ]);

        // Must be a participant to access staff positions
        if (!$participant) {
            return false;
        }

        return match ($attribute) {
            self::UPDATE_POSITION => $this->staffPositionService->canUpdatePosition($participant),
            self::VIEW_POSITIONS => true, // All participants can view (filtered by visibility rules in service)
            self::VIEW_ALL_POSITIONS => $this->staffPositionService->canViewAllPositions($participant),
            default => false,
        };
    }
}
