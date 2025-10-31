<?php

namespace App\Domain\Core\Controller\Backoffice;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Form\LarpPropertiesType;
use App\Domain\Core\Service\Workflow\LarpWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/backoffice/larp/{larp}/status', name: 'backoffice_larp_status_')]
class StatusController extends AbstractController
{
    public function __construct(
        private readonly LarpWorkflowService $workflowService,
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Larp $larp): Response
    {
        $this->denyAccessUnlessGranted('MANAGE_LARP_GENERAL_SETTINGS', $larp);

        $availableTransitions = $this->workflowService->getAvailableTransitionsWithLabels($larp);
        $currentStatus = $larp->getStatus();
        $allStatuses = $this->workflowService->getAllStatuses();

        $propertiesForm = $this->createForm(LarpPropertiesType::class, $larp);

        return $this->render('backoffice/larp/status/index.html.twig', [
            'larp' => $larp,
            'currentStatus' => $currentStatus,
            'availableTransitions' => $availableTransitions,
            'allStatuses' => $allStatuses,
            'propertiesForm' => $propertiesForm->createView(),
        ]);
    }

    #[Route('/update-properties', name: 'update_properties', methods: ['POST'])]
    public function updateProperties(Larp $larp, Request $request): Response
    {
        $this->denyAccessUnlessGranted('MANAGE_LARP_GENERAL_SETTINGS', $larp);

        $form = $this->createForm(LarpPropertiesType::class, $larp);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle header image file upload
            /** @var UploadedFile|null $headerImageFile */
            $headerImageFile = $form->get('headerImageFile')->getData();
            if ($headerImageFile) {
                $originalFilename = pathinfo($headerImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $headerImageFile->guessExtension();

                try {
                    $headerImageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/larps',
                        $newFilename
                    );
                    $larp->setHeaderImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Could not upload header image: ' . $e->getMessage());
                }
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'LARP properties updated successfully.');
        } else {
            $this->addFlash('error', 'Failed to update LARP properties. Please check the form for errors.');
        }

        return $this->redirectToRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
    }

    #[Route('/transition/{transitionName}', name: 'transition', methods: ['POST'])]
    public function transition(Larp $larp, string $transitionName, Request $request): Response
    {
        $this->denyAccessUnlessGranted('MANAGE_LARP_GENERAL_SETTINGS', $larp);

        if (!$this->isCsrfTokenValid('larp_transition_' . $larp->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
        }

        // Check validation errors
        $validationErrors = $this->workflowService->getTransitionValidationErrors($larp, $transitionName);
        if ($validationErrors !== []) {
            foreach ($validationErrors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
        }

        if (!$this->workflowService->canTransition($larp, $transitionName)) {
            $this->addFlash('error', 'This transition is not allowed.');
            return $this->redirectToRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
        }

        $success = $this->workflowService->applyTransition($larp, $transitionName);

        if ($success) {
            $this->addFlash('success', 'Status updated successfully.');
        } else {
            $this->addFlash('error', 'Failed to update status.');
        }

        return $this->redirectToRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
    }

    #[Route('/api/transitions', name: 'api_transitions', methods: ['GET'])]
    public function getAvailableTransitions(Larp $larp): JsonResponse
    {
        $transitions = $this->workflowService->getAvailableTransitionsWithLabels($larp);
        
        return $this->json([
            'current_status' => $larp->getStatus()->value,
            'available_transitions' => $transitions,
        ]);
    }
}
