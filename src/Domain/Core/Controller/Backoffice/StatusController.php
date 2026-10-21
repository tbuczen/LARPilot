<?php

namespace App\Domain\Core\Controller\Backoffice;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Form\LarpPropertiesType;
use App\Domain\Core\Security\Voter\LarpCancelVoter;
use App\Domain\Core\Security\Voter\LarpDeleteVoter;
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
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/backoffice/larp/{larp}/status', name: 'backoffice_larp_status_')]
class StatusController extends AbstractController
{
    public function __construct(
        private readonly LarpWorkflowService $workflowService,
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Larp $larp): Response
    {
        $this->denyAccessUnlessGranted('MANAGE_LARP_GENERAL_SETTINGS', $larp);

        $availableTransitions = $this->workflowService->getAvailableTransitionsWithLabels($larp);
        unset($availableTransitions[LarpWorkflowService::CANCEL_TRANSITION]);
        $currentStatus = $larp->getStatus();
        $allStatuses = $this->workflowService->getAllStatuses();

        $propertiesForm = $this->createForm(LarpPropertiesType::class, $larp);

        return $this->render('backoffice/larp/status/index.html.twig', [
            'larp' => $larp,
            'currentStatus' => $currentStatus,
            'availableTransitions' => $availableTransitions,
            'allStatuses' => $allStatuses,
            'propertiesForm' => $propertiesForm->createView(),
            'canCancel' => $this->isGranted(LarpCancelVoter::CANCEL, $larp),
            'canDelete' => $this->isGranted(LarpDeleteVoter::DELETE, $larp),
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
                    $this->addFlash('error', $this->translator->trans('larp.status.flash.header_upload_failed', ['%error%' => $e->getMessage()]));
                }
            }

            $this->entityManager->flush();
            $this->addFlash('success', $this->translator->trans('larp.status.flash.properties_updated'));
        } else {
            $this->addFlash('error', $this->translator->trans('larp.status.flash.properties_failed'));
        }

        return $this->redirectToRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
    }

    #[Route('/transition/{transitionName}', name: 'transition', methods: ['POST'])]
    public function transition(Larp $larp, string $transitionName, Request $request): Response
    {
        $this->denyAccessUnlessGranted('MANAGE_LARP_GENERAL_SETTINGS', $larp);

        $isCancellation = $transitionName === LarpWorkflowService::CANCEL_TRANSITION;
        if ($isCancellation) {
            $this->denyAccessUnlessGranted(LarpCancelVoter::CANCEL, $larp);
        }

        if (!$this->isCsrfTokenValid('larp_transition_' . $larp->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('larp.status.flash.invalid_csrf'));
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
            $this->addFlash('error', $this->translator->trans('larp.status.flash.transition_not_allowed'));
            return $this->redirectToRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
        }

        $success = $this->workflowService->applyTransition($larp, $transitionName);

        if ($success && $isCancellation) {
            $this->addFlash('success', $this->translator->trans('larp.cancel.success'));
        } elseif ($success) {
            $this->addFlash('success', $this->translator->trans('larp.status.flash.status_updated'));
        } else {
            $this->addFlash('error', $this->translator->trans('larp.status.flash.status_failed'));
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
