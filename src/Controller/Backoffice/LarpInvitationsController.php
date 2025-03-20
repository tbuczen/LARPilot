<?php

namespace App\Controller\Backoffice;

use App\Domain\Larp\UseCase\GenerateInvitation\GenerateInvitationCommand;
use App\Domain\Larp\UseCase\GenerateInvitation\GenerateInvitationHandler;
use App\Enum\UserRole;
use App\Repository\LarpRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp', name: 'backoffice_larp_')]
class LarpInvitationsController extends AbstractController
{

    #[Route('/{id}/generate-invitation', name: 'generate_invitation', methods: ['POST'])]
    public function generateInvitation(string $id, Request $request, GenerateInvitationHandler $handler): Response
    {
        $defaultValidTo = (new \DateTime('+1 week'))->format('Y-m-d H:i:d');
        $validTo = $request->request->get('validTo', $defaultValidTo);
        $invitedRoleValue = $request->request->get('invitedRole', UserRole::STAFF->value);
        $invitedRole = UserRole::from($invitedRoleValue);

        $command = new GenerateInvitationCommand(
            larpId: $id,
            validTo: new \DateTimeImmutable($validTo),
            invitedRole: $invitedRole
        );
        $dto = $handler->handle($command);

        $this->addFlash('success', 'Invitation generated successfully.');

        return $this->redirectToRoute('backoffice_larp_invitations', ['id' => $id]);
    }

    #[Route('/{id}/invitations', name: 'invitations', methods: ['GET'])]
    public function invitations(string $id, LarpRepository $larpRepository): Response
    {
        $larp = $larpRepository->find($id);
        if (!$larp) {
            throw $this->createNotFoundException('Larp not found.');
        }

        // Assume you have a service that generates an invitation link,
        // or you call the GenerateInvitationHandler here.
        // For simplicity, you can render a page where the organizer can click a button to generate an invitation.

        return $this->render('backoffice/larp/invitations.html.twig', [
            'larp' => $larp,
            // Pass existing invitations if needed
        ]);
    }
}