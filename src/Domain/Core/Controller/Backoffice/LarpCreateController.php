<?php

declare(strict_types=1);

namespace App\Domain\Core\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Form\LarpWizardType;
use App\Domain\Core\Security\Voter\LarpCreationVoter;
use App\Domain\Core\Service\LarpManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/larp/create', name: 'backoffice_larp_create', methods: ['GET', 'POST'])]
class LarpCreateController extends AbstractController
{
    public function __invoke(Request $request, LarpManager $larpManager, TranslatorInterface $translator): Response
    {
        if (!$this->isGranted(LarpCreationVoter::CREATE)) {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user->isApproved()) {
                $this->addFlash('error', $translator->trans('flash.account_not_approved'));

                return $this->redirectToRoute('backoffice_larp_list');
            }

            $this->addFlash('error', $translator->trans('flash.larp_limit_reached', [
                '%current%' => $user->getOrganizerLarpCount(),
                '%max%' => $user->getMaxLarpsAllowed() ?? 0,
            ]));

            return $this->redirectToRoute('backoffice_larp_list');
        }

        $larp = new Larp();
        $form = $this->createForm(LarpWizardType::class, $larp);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $larp->setStatus(LarpStageStatus::DRAFT);

            /** @var User $user */
            $user = $this->getUser();
            $larp = $larpManager->createLarp($larp, $user);

            $this->addFlash('success', $translator->trans('flash.larp_created_draft'));

            return $this->redirectToRoute('backoffice_larp_dashboard', ['larp' => $larp->getId()->toRfc4122()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', $translator->trans('flash.validation_error'));
        }

        return $this->render('backoffice/larp/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
