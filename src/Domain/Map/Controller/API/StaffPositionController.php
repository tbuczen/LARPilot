<?php

declare(strict_types=1);

namespace App\Domain\Map\Controller\API;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\LarpParticipantRepository;
use App\Domain\Map\Entity\GameMap;
use App\Domain\Map\Security\Voter\StaffPositionVoter;
use App\Domain\Map\Service\StaffPositionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API controller for staff position management.
 * Provides JSON endpoints for fetching and updating positions via AJAX.
 */
#[Route('/larp/{larp}/map/{map}/staff-positions', name: 'api_staff_position_')]
#[IsGranted('ROLE_USER')]
class StaffPositionController extends AbstractController
{
    /**
     * Get all visible staff positions for a map.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Larp $larp,
        GameMap $map,
        LarpParticipantRepository $participantRepository,
        StaffPositionService $positionService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(StaffPositionVoter::VIEW_POSITIONS, $larp);

        $user = $this->getUser();
        $participant = $participantRepository->findOneBy(['user' => $user, 'larp' => $larp]);

        if (!$participant) {
            return new JsonResponse(['error' => 'Not a participant'], Response::HTTP_FORBIDDEN);
        }

        // Get positions filtered by visibility rules
        $positions = $positionService->getVisiblePositions($map, $participant);

        // Convert to array for JSON
        $positionsData = array_map(
            fn ($pos) => $positionService->positionToArray($pos),
            $positions
        );

        return new JsonResponse([
            'positions' => $positionsData,
            'canViewAll' => $positionService->canViewAllPositions($participant),
            'canUpdate' => $positionService->canUpdatePosition($participant),
        ]);
    }

    /**
     * Update current user's position on the map.
     */
    #[Route('', name: 'update', methods: ['POST'])]
    public function update(
        Request $request,
        Larp $larp,
        GameMap $map,
        LarpParticipantRepository $participantRepository,
        StaffPositionService $positionService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(StaffPositionVoter::UPDATE_POSITION, $larp);

        $user = $this->getUser();
        $participant = $participantRepository->findOneBy(['user' => $user, 'larp' => $larp]);

        if (!$participant) {
            return new JsonResponse(['error' => 'Not a participant'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        $gridCell = $data['gridCell'] ?? null;
        $statusNote = $data['statusNote'] ?? null;

        if (!$gridCell) {
            return new JsonResponse(['error' => 'gridCell is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $position = $positionService->updatePosition($participant, $map, $gridCell, $statusNote);

            return new JsonResponse([
                'success' => true,
                'position' => $positionService->positionToArray($position),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Remove current user's position from the map.
     */
    #[Route('', name: 'remove', methods: ['DELETE'])]
    public function remove(
        Larp $larp,
        GameMap $map,
        LarpParticipantRepository $participantRepository,
        StaffPositionService $positionService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(StaffPositionVoter::UPDATE_POSITION, $larp);

        $user = $this->getUser();
        $participant = $participantRepository->findOneBy(['user' => $user, 'larp' => $larp]);

        if (!$participant) {
            return new JsonResponse(['error' => 'Not a participant'], Response::HTTP_FORBIDDEN);
        }

        $positionService->removePosition($participant, $map);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Get current user's position on the map.
     */
    #[Route('/me', name: 'my_position', methods: ['GET'])]
    public function myPosition(
        Larp $larp,
        GameMap $map,
        LarpParticipantRepository $participantRepository,
        StaffPositionService $positionService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(StaffPositionVoter::UPDATE_POSITION, $larp);

        $user = $this->getUser();
        $participant = $participantRepository->findOneBy(['user' => $user, 'larp' => $larp]);

        if (!$participant) {
            return new JsonResponse(['error' => 'Not a participant'], Response::HTTP_FORBIDDEN);
        }

        $position = $positionService->getPosition($participant, $map);

        if (!$position) {
            return new JsonResponse(['position' => null]);
        }

        return new JsonResponse([
            'position' => $positionService->positionToArray($position),
        ]);
    }
}
