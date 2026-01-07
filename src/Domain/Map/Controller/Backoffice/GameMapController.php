<?php

declare(strict_types=1);

namespace App\Domain\Map\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Map\Entity\GameMap;
use App\Domain\Map\Entity\MapLocation;
use App\Domain\Map\Form\Filter\GameMapFilterType;
use App\Domain\Map\Form\GameMapType;
use App\Domain\Map\Form\MapLocationType;
use App\Domain\Map\Repository\GameMapRepository;
use App\Domain\Map\Repository\MapLocationRepository;
use App\Domain\Map\Repository\StaffPositionRepository;
use App\Domain\Map\Service\StaffPositionService;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/larp/{larp}/map/', name: 'backoffice_larp_map_')]
class GameMapController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET'])]
    public function list(Request $request, Larp $larp, GameMapRepository $repository): Response
    {
        $filterForm = $this->createForm(GameMapFilterType::class);
        $filterForm->handleRequest($request);

        $qb = $repository->createQueryBuilder('m')
            ->where('m.larp = :larp')
            ->setParameter('larp', $larp);

        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        $sort = $request->query->get('sort', 'name');
        $dir = $request->query->get('dir', 'asc');
        $qb->orderBy('m.' . $sort, $dir);

        $maps = $qb->getQuery()->getResult();

        return $this->render('backoffice/larp/map/list.html.twig', [
            'maps' => $maps,
            'larp' => $larp,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('{map}', name: 'modify', defaults: ['map' => null], methods: ['GET', 'POST'])]
    public function modify(
        Request $request,
        Larp $larp,
        GameMapRepository $mapRepository,
        SluggerInterface $slugger,
        ?GameMap $map = null
    ): Response {
        $isNew = !($map instanceof GameMap);

        if ($isNew) {
            $map = new GameMap();
            $map->setLarp($larp);
        }

        $form = $this->createForm(GameMapType::class, $map);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('maps_directory'),
                        $newFilename
                    );
                    $map->setImageFile($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', $this->translator->trans('larp.map.upload_error'));
                }
            }

            $mapRepository->save($map);
            $this->addFlash('success', $this->translator->trans('success_save'));

            return $this->redirectToRoute('backoffice_larp_map_view', [
                'larp' => $larp->getId(),
                'map' => $map->getId(),
            ]);
        }

        return $this->render('backoffice/larp/map/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'map' => $map,
            'isNew' => $isNew,
        ]);
    }

    #[Route('{map}/view', name: 'view', methods: ['GET'])]
    public function view(
        Larp $larp,
        GameMap $map,
        MapLocationRepository $locationRepository,
        StaffPositionRepository $staffPositionRepository,
        StaffPositionService $staffPositionService,
        \App\Domain\Core\Repository\LarpParticipantRepository $participantRepository,
    ): Response {
        $locations = $locationRepository->findByMap($map);

        $locationsData = array_map(function (MapLocation $location) {
            $tagNames = $location->getTags()->map(fn ($tag) => $tag->getTitle())->toArray();

            return [
                'id' => $location->getId()->toString(),
                'name' => $location->getName(),
                'positionX' => $location->getPositionX(),
                'positionY' => $location->getPositionY(),
                'shape' => $location->getShape()->value,
                'color' => $location->getEffectiveColor(),
                'type' => $location->getType()?->value,
                'capacity' => $location->getCapacity(),
                'description' => $location->getDescription(),
                'tags' => $tagNames,
            ];
        }, $locations);

        // Get all staff positions for the map (backoffice users see all)
        $staffPositions = $staffPositionRepository->findByMap($map);
        $staffPositionsData = array_map(
            fn ($pos) => $staffPositionService->positionToArray($pos),
            $staffPositions
        );

        // Get current user's participant and position
        $user = $this->getUser();
        $participant = $participantRepository->findOneBy(['user' => $user, 'larp' => $larp]);
        $myPosition = null;
        $canUpdatePosition = false;

        if ($participant) {
            $canUpdatePosition = $staffPositionService->canUpdatePosition($participant);
            if ($canUpdatePosition) {
                $myPosition = $staffPositionService->getPosition($participant, $map);
            }
        }

        return $this->render('backoffice/larp/map/view.html.twig', [
            'larp' => $larp,
            'map' => $map,
            'locations' => $locations,
            'locationsData' => $locationsData,
            'staffPositions' => $staffPositions,
            'staffPositionsData' => $staffPositionsData,
            'myPosition' => $myPosition,
            'canUpdatePosition' => $canUpdatePosition,
        ]);
    }

    #[Route('{map}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Larp $larp, GameMapRepository $mapRepository, GameMap $map): Response
    {
        $mapRepository->remove($map);
        $this->addFlash('success', $this->translator->trans('success_delete'));

        return $this->redirectToRoute('backoffice_larp_map_list', ['larp' => $larp->getId()]);
    }

    #[Route('{map}/location/{location}', name: 'location_modify', defaults: ['location' => null], methods: ['GET', 'POST'])]
    public function locationModify(
        Request $request,
        Larp $larp,
        GameMap $map,
        MapLocationRepository $locationRepository,
        ?MapLocation $location = null
    ): Response {
        $isNew = !($location instanceof MapLocation);

        if ($isNew) {
            $location = new MapLocation();
            $location->setMap($map);
        }

        $form = $this->createForm(MapLocationType::class, $location, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $locationRepository->save($location);
            $this->addFlash('success', $this->translator->trans('success_save'));

            return $this->redirectToRoute('backoffice_larp_map_view', [
                'larp' => $larp->getId(),
                'map' => $map->getId(),
            ]);
        }

        return $this->render('backoffice/larp/map/location_modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'map' => $map,
            'location' => $location,
            'isNew' => $isNew,
        ]);
    }

    #[Route('{map}/location/{location}/delete', name: 'location_delete', methods: ['POST'])]
    public function locationDelete(
        Larp $larp,
        GameMap $map,
        MapLocationRepository $locationRepository,
        MapLocation $location
    ): Response {
        $locationRepository->remove($location);
        $this->addFlash('success', $this->translator->trans('success_delete'));

        return $this->redirectToRoute('backoffice_larp_map_view', [
            'larp' => $larp->getId(),
            'map' => $map->getId(),
        ]);
    }
}
