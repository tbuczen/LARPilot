<?php

declare(strict_types=1);

namespace App\Domain\Map\Controller\Participant;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\LarpParticipantRepository;
use App\Domain\Map\Entity\GameMap;
use App\Domain\Map\Repository\GameMapRepository;
use App\Domain\Map\Security\Voter\StaffPositionVoter;
use App\Domain\Map\Service\StaffPositionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for staff members to update their positions on game maps.
 * Mobile-friendly interface for organizers to tap on grid to update their location.
 */
#[Route('/larp/{larp}/staff-position', name: 'participant_staff_position_')]
#[IsGranted('ROLE_USER')]
class StaffPositionController extends BaseController
{
    /**
     * Map selection page - shows available maps for the LARP.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Larp $larp,
        GameMapRepository $mapRepository,
    ): Response {
        $this->denyAccessUnlessGranted(StaffPositionVoter::UPDATE_POSITION, $larp);

        $maps = $mapRepository->findByLarp($larp);

        return $this->render('participant/map/position_index.html.twig', [
            'larp' => $larp,
            'maps' => $maps,
        ]);
    }

    /**
     * Position update page - mobile interface to tap on grid to update position.
     */
    #[Route('/map/{map}', name: 'update', methods: ['GET', 'POST'])]
    public function update(
        Request $request,
        Larp $larp,
        GameMap $map,
        LarpParticipantRepository $participantRepository,
        StaffPositionService $positionService,
    ): Response {
        $this->denyAccessUnlessGranted(StaffPositionVoter::UPDATE_POSITION, $larp);

        $user = $this->getUser();
        $participant = $participantRepository->findOneBy(['user' => $user, 'larp' => $larp]);

        if (!$participant) {
            throw $this->createAccessDeniedException('You are not a participant in this LARP');
        }

        // Get current position if exists
        $currentPosition = $positionService->getPosition($participant, $map);

        if ($request->isMethod('POST')) {
            $gridCell = $request->request->get('gridCell');
            $statusNote = $request->request->get('statusNote');

            if ($gridCell) {
                try {
                    $positionService->updatePosition($participant, $map, $gridCell, $statusNote);
                    $this->addFlash('success', $this->translator->trans('staff_position.updated'));

                    return $this->redirectToRoute('participant_staff_position_update', [
                        'larp' => $larp->getId(),
                        'map' => $map->getId(),
                    ]);
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('error', $this->translator->trans('staff_position.invalid_cell'));
                }
            } else {
                $this->addFlash('error', $this->translator->trans('staff_position.select_cell'));
            }
        }

        return $this->render('participant/map/position_update.html.twig', [
            'larp' => $larp,
            'map' => $map,
            'currentPosition' => $currentPosition,
        ]);
    }

    /**
     * View staff positions page - shows all visible staff positions on the map.
     */
    #[Route('/map/{map}/view', name: 'view', methods: ['GET'])]
    public function view(
        Larp $larp,
        GameMap $map,
        LarpParticipantRepository $participantRepository,
        StaffPositionService $positionService,
    ): Response {
        $this->denyAccessUnlessGranted(StaffPositionVoter::VIEW_POSITIONS, $larp);

        $user = $this->getUser();
        $participant = $participantRepository->findOneBy(['user' => $user, 'larp' => $larp]);

        if (!$participant) {
            throw $this->createAccessDeniedException('You are not a participant in this LARP');
        }

        // Get positions filtered by visibility rules
        $positions = $positionService->getVisiblePositions($map, $participant);

        // Convert to array for JSON
        $positionsData = array_map(
            fn ($pos) => $positionService->positionToArray($pos),
            $positions
        );

        $canViewAll = $positionService->canViewAllPositions($participant);

        return $this->render('participant/map/position_view.html.twig', [
            'larp' => $larp,
            'map' => $map,
            'positions' => $positions,
            'positionsData' => $positionsData,
            'canViewAll' => $canViewAll,
        ]);
    }

    /**
     * Remove current position from map.
     */
    #[Route('/map/{map}/remove', name: 'remove', methods: ['POST'])]
    public function remove(
        Larp $larp,
        GameMap $map,
        LarpParticipantRepository $participantRepository,
        StaffPositionService $positionService,
    ): Response {
        $this->denyAccessUnlessGranted(StaffPositionVoter::UPDATE_POSITION, $larp);

        $user = $this->getUser();
        $participant = $participantRepository->findOneBy(['user' => $user, 'larp' => $larp]);

        if (!$participant) {
            throw $this->createAccessDeniedException('You are not a participant in this LARP');
        }

        $positionService->removePosition($participant, $map);
        $this->addFlash('success', $this->translator->trans('staff_position.removed'));

        return $this->redirectToRoute('participant_staff_position_index', [
            'larp' => $larp->getId(),
        ]);
    }
}
