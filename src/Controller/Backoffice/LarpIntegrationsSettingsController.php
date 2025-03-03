<?php

namespace App\Controller\Backoffice;

use App\Domain\Larp\UseCase\GenerateInvitation\GenerateInvitationCommand;
use App\Domain\Larp\UseCase\GenerateInvitation\GenerateInvitationHandler;
use App\Domain\Larp\UseCase\SubmitLarp\SubmitLarpCommand;
use App\Domain\Larp\UseCase\SubmitLarp\SubmitLarpHandler;
use App\Entity\Larp;
use App\Entity\LarpIntegration;
use App\Enum\LarpIntegrationProvider;
use App\Enum\UserRole;
use App\Form\LarpType;
use App\Repository\LarpIntegrationRepository;
use App\Repository\LarpRepository;
use App\Security\GoogleAuthenticator;
use App\Security\Voter\Backoffice\Larp\LarpDetailsVoter;
use App\Service\LarpIntegrationManager;
use Google\Client;
use Google\Service\Drive;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/larp', name: 'backoffice_larp_')]
class LarpIntegrationsSettingsController extends AbstractController
{

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly LarpRepository $larpRepository,
        private readonly LarpIntegrationManager $larpIntegrationManager,
    )
    {
    }

    #[Route('/{id}/integration-settings', name: 'integration_settings', methods: ['GET', 'POST'])]
    public function integrationsSettings(
        string                    $id,
        LarpIntegrationRepository $larpIntegrationRepository
    ): Response
    {
        $larp = $this->larpRepository->find($id);
        if (!$larp) {
            throw $this->createNotFoundException('Larp not found.');
        }

        $integrations = $larpIntegrationRepository->findAllByLarp($id);
        $this->larpIntegrationManager->decorateIntegrationsWithClient($integrations);

        return $this->render('backoffice/larp/integrationsSettings.html.twig', [
            'larp' => $larp,
            'integrations' => $integrations,
        ]);
    }

    /** @see GoogleAuthenticator */
    #[Route('/{id}/integration/connect/googleDrive', name: 'google_drive_connect', methods: ['GET', 'POST'])]
    public function connectGoogleDrive(
        string           $id,
        SessionInterface $session
    ): Response
    {
        $larp = $this->larpRepository->find($id);
        if (!$larp) {
            throw $this->createNotFoundException('Larp not found.');
        }

        $client = $this->clientRegistry->getClient(LarpIntegrationProvider::Google->value);
        $session->set('current_larp_id', $id);
        return $client
            ->redirect(LarpIntegrationManager::GOOGLE_SCOPES, [
                'redirect_uri' => $this->generateUrl('backoffice_larp_google_drive_connect_check', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);
    }

    #[Route('/integration/connect/googleDrive/check', name: 'google_drive_connect_check')]
    public function connectGoogleCheck(SessionInterface          $session,): RedirectResponse
    {
        /** @var GoogleClient $client */
        $oauthClient = $this->clientRegistry->getClient(LarpIntegrationProvider::Google->value);
        $larpId = $session->get('current_larp_id');
        $this->larpIntegrationManager->createGoogleDriveIntegration($oauthClient->getAccessToken(), $larpId);

//        $client = new Client([
//            'client_id' => getenv('OAUTH_GOOGLE_CLIENT_ID'),
//            'client_secret' => getenv('OAUTH_GOOGLE_CLIENT_SECRET'),
//        ]);
//        $client->setAccessToken($accessToken->getToken());
//        $drive = new Drive($client);

        $session->remove('current_larp_id');
        return $this->redirectToRoute('backoffice_larp_details', ['id' => $larpId]);
    }

}