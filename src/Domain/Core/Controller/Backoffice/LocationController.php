<?php

namespace App\Domain\Core\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Location;
use App\Domain\Core\Form\LocationRejectionType;
use App\Domain\Core\Form\LocationType;
use App\Domain\Core\Repository\LocationRepository;
use App\Domain\Core\Security\Voter\LocationVoter;
use App\Domain\Core\Service\LocationApprovalService;
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
        private readonly LocationRepository      $locationRepository,
        private readonly EntityManagerInterface  $entityManager,
        private readonly LocationApprovalService $approvalService
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
        /** @var User $user */
        $user = $this->getUser();
        $isNew = false;

        if (!$location instanceof Location) {
            // Creating a new location
            $location = new Location();
            $isNew = true;

            // Check if user can create locations (only APPROVED users)
            if (!$this->approvalService->canUserCreateLocation($user)) {
                $this->addFlash('error', 'Only approved users can create locations. Please contact an administrator.');
                return $this->redirectToRoute('backoffice_location_list');
            }
        } else {
            // Editing existing location - check permissions
            if (!$this->isGranted(LocationVoter::EDIT, $location)) {
                throw $this->createAccessDeniedException('You cannot edit this location.');
            }
        }

        $form = $this->createForm(LocationType::class, $location, [
            'show_captcha' => $isNew && !$this->isGranted('ROLE_SUPER_ADMIN'), // Show CAPTCHA for non-admin new locations
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                // Set approval status for new locations
                if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                    // Super admin locations are auto-approved
                    $this->approvalService->autoApprove($location, $user);
                    $this->addFlash('success', 'Location created and approved.');
                } else {
                    // Regular user locations need approval
                    $location->setApprovalStatus(LocationApprovalStatus::PENDING);
                    $this->addFlash('success', 'Location submitted for approval. It will be reviewed by an administrator.');
                }
            } else {
                $this->addFlash('success', 'Location updated successfully.');
            }

            $locationRepository->save($location);
            return $this->redirectToRoute('backoffice_location_list');
        }

        return $this->render('backoffice/location/modify.html.twig', [
            'location' => $location,
            'isNew' => $isNew,
            'form' => $form,
            'googleMapsApiKey' => $this->getParameter('google_maps_api_key'),
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
            'googleMapsApiKey' => $this->getParameter('google_maps_api_key'),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, Location $location): Response
    {
        // Use the voter for permission check
        if (!$this->isGranted(LocationVoter::DELETE, $location)) {
            throw $this->createAccessDeniedException('You cannot delete this location.');
        }

        if ($this->isCsrfTokenValid('delete' . $location->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($location);
            $this->entityManager->flush();
            $this->addFlash('success', 'Location deleted successfully.');
        }

        return $this->redirectToRoute('backoffice_location_list');
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function approve(Request $request, Location $location): Response
    {
        if (!$this->isCsrfTokenValid('approve' . $location->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('backoffice_location_list');
        }

        /** @var User $user */
        $user = $this->getUser();

        $this->approvalService->approve($location, $user);
        $this->addFlash('success', sprintf('Location "%s" has been approved.', $location->getTitle()));

        return $this->redirectToRoute('backoffice_location_list');
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function reject(Request $request, Location $location): Response
    {
        $form = $this->createForm(LocationRejectionType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $reason = $form->get('reason')->getData();

            $this->approvalService->reject($location, $user, $reason);
            $this->addFlash('success', sprintf('Location "%s" has been rejected.', $location->getTitle()));

            return $this->redirectToRoute('backoffice_location_list');
        }

        return $this->render('backoffice/location/reject.html.twig', [
            'location' => $location,
            'form' => $form,
        ]);
    }
}
