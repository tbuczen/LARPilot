<?php

namespace App\Controller\Backoffice;

use App\Domain\Larp\UseCase\GenerateInvitation\GenerateInvitationCommand;
use App\Domain\Larp\UseCase\GenerateInvitation\GenerateInvitationHandler;
use App\Domain\Larp\UseCase\SubmitLarp\SubmitLarpCommand;
use App\Domain\Larp\UseCase\SubmitLarp\SubmitLarpHandler;
use App\Entity\Larp;
use App\Enum\LarpIntegrationProvider;
use App\Enum\UserRole;
use App\Form\LarpType;
use App\Repository\LarpRepository;
use App\Security\GoogleAuthenticator;
use App\Security\Voter\Backoffice\Larp\LarpDetailsVoter;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/larp', name: 'backoffice_larp_')]
class LarpIntegrationsSettingsController extends AbstractController
{

    #[Route('/{id}/integration-settings', name: 'integration_settings', methods: ['GET', 'POST'])]
    public function integrationsSettings(string $id, Request $request, LarpRepository $larpRepository): Response
    {
        $larp = $larpRepository->find($id);
        if (!$larp) {
            throw $this->createNotFoundException('Larp not found.');
        }

        return $this->render('backoffice/larp/integrationsSettings.html.twig', [
            'larp' => $larp,
            // Pass existing invitations if needed
        ]);
    }

    /** @see GoogleAuthenticator */
    #[Route('/{id}/integration/connect/googleDrive', name: 'google_drive_connect', methods: ['GET', 'POST'])]
    public function connectGoogleDrive(ClientRegistry $clientRegistry, string $id, LarpRepository $larpRepository): Response
    {
        $larp = $larpRepository->find($id);
        if (!$larp) {
            throw $this->createNotFoundException('Larp not found.');
        }

        $client = $clientRegistry->getClient(LarpIntegrationProvider::Google->value);
        return $client
            ->redirect(['https://www.googleapis.com/auth/drive.file'], [
                'redirect_uri' => $this->generateUrl('backoffice_larp_google_drive_connect_check', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);
    }

    #[Route('/{id}/integration/connect/googleDrive/check', name: 'google_drive_connect_check')]
    public function connectGoogleCheck(Request $request, ClientRegistry $clientRegistry): RedirectResponse
    {
        // This route handles the OAuth callback.
        // Here you can retrieve the access token, refresh token, expiration,
        // and other user data. Then, create or update a LarpIntegration entity.
        // (Don't forget to associate it with the appropriate Larp.)

        // For example:
         $client = $clientRegistry->getClient('google');
         $token = $client->getAccessToken();
        // Save token details to the database.

        // After processing, redirect back to the integration settings page.
        return $this->redirectToRoute('backoffice_larp_integration_settings');
    }

}