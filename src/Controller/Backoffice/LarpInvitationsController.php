<?php

namespace App\Controller\Backoffice;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\LarpInvitation;
use App\Form\InvitationType;
use App\Repository\LarpInvitationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}', name: 'backoffice_larp_invitations_')]
class LarpInvitationsController extends BaseController
{

    #[Route('/invitations', name: 'list', methods: ['GET', 'POST'])]
    public function list(Request $request, Larp $larp, LarpInvitationRepository $invitationRepository, ?LarpInvitation $invitation = null): Response
    {

        if ($invitation !== null) {
            $invitation = new LarpInvitation();
            $invitation->setLarp($larp);
        }

        $form = $this->createForm(InvitationType::class, $invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitationRepository->save($invitation);

            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
            return $this->redirectToRoute('backoffice_larp_invitations_list', ['id' => $larp->getId()]);
        }

        $invitations = $invitationRepository->findBy(['larp' => $larp]);
        return $this->render('backoffice/larp/invitation/list.html.twig', [
            'larp' => $larp,
            'invitations' => $invitations,
        ]);
    }

    #[Route('/invitation/{invitation}', name: 'modify', defaults: ['invitation' => null], methods: ['GET', 'POST'])]
    public function modify(
        Request                  $request,
        Larp                     $larp,
        LarpInvitationRepository $invitationRepository,
        ?LarpInvitation          $invitation = null,
    ): Response
    {

        if (!$invitation) {
            $invitation = new LarpInvitation();
        }
        $form = $this->createForm(InvitationType::class, $invitation, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitation->setLarp($larp);
            $invitationRepository->save($invitation);
            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
            return $this->redirectToRoute('backoffice_larp_invitations_list', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/larp/invitation/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
        ]);
    }

    #[Route('/invitation/{invitation}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(
        Larp                     $larp,
        LarpInvitationRepository $invitationRepository,
        LarpInvitation           $invitation,
    ): Response
    {
        $invitationRepository->remove($invitation);
        $this->addFlash('success', $this->translator->trans('backoffice.common.success_delete'));

        return $this->redirectToRoute('backoffice_larp_invitations_list', [
            'larp' => $larp->getId(),
        ]);
    }

}