<?php

namespace App\Domain\Core\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Location;
use App\Domain\Core\Form\LocationType;
use App\Domain\Core\Repository\LocationRepository;
use App\Domain\Core\Security\Voter\LocationVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/location', name: 'backoffice_location_')]
#[IsGranted('ROLE_USER')]
class LocationController extends AbstractController
{
    public function __construct(
        private readonly LocationRepository     $locationRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            // Super admin can see all locations
            $locations = $this->locationRepository->findAll();
        } else {
            // Organizer can only see their own locations and public ones
            $locations = $this->locationRepository->findActiveAndPublicForUser($user);
        }

        return $this->render('backoffice/location/list.html.twig', [
            'locations' => $locations,
        ]);
    }

    #[Route('/{location}', name: 'modify_global', defaults: ['location' => null], methods: ['GET', 'POST'])]
    public function modifyGlobal(
        Request            $request,
        LocationRepository $locationRepository,
        ?Location          $location = null
    ): Response {
        if (!$location instanceof Location) {
            $location = new Location();
        }

        // Check if user has organizer role or is super admin
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException('You need ROLE_SUPER_ADMIN to create global locations.');
        }

        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $locationRepository->save($location);

            $this->addFlash('success', 'Location created successfully.');
            return $this->redirectToRoute('backoffice_location_list');
        }

        return $this->render('backoffice/location/modify.html.twig', [
            'location' => $location,
            'form' => $form,
        ]);
    }

    #[Route('/larp/{larp}/location/{location}', name: 'modify_for_larp', defaults: ['location' => null], methods: ['GET', 'POST'])]
    public function modifyForLarp(
        Request            $request,
        Larp               $larp,
        LocationRepository $locationRepository,
        ?Location          $location = null
    ): Response {
        if (!$location instanceof Location) {
            $location = new Location();
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->redirectToRoute('backoffice_location_modify_global');
        }

        if (!$this->isGranted(LocationVoter::MANAGE, $larp)) {
            return $this->redirectToRoute('public_larp_list', [], 403);
        }

        if ($larp->getLocation() !== $location) {
            //Core admin can only modify larp location, and there can be only one per larp
            return $this->redirectToRoute('backoffice_location_modify_for_larp', [
                'larp' => $larp,
                'location' => $location,
            ]);
        }

        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $locationRepository->save($location);

            $this->addFlash('success', 'Location created successfully.');
            return $this->redirectToRoute('backoffice_location_list');
        }

        return $this->render('backoffice/location/modify.html.twig', [
            'location' => $location,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Location $location): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $location->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException('You can only delete locations you created.');
        }

        if ($this->isCsrfTokenValid('delete' . $location->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($location);
            $this->entityManager->flush();
            $this->addFlash('success', 'Location deleted successfully.');
        }

        return $this->redirectToRoute('backoffice_location_list');
    }
}
