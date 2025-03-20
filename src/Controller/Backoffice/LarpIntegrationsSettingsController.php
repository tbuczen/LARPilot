<?php

namespace App\Controller\Backoffice;

use App\Domain\Integrations\UseCase\ApplyFilesPermission\ApplyFilesPermissionsCommand;
use App\Domain\Integrations\UseCase\ApplyFilesPermission\ApplyFilesPermissionsHandler;
use App\Entity\LarpIntegration;
use App\Enum\LarpIntegrationProvider;
use App\Repository\LarpIntegrationRepository;
use App\Repository\LarpRepository;
use App\Security\GoogleAuthenticator;
use App\Service\Integrations\IntegrationServiceProvider;
use App\Service\Integrations\LarpIntegrationManager;
use Google\Service\Drive;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

#[Route('/larp', name: 'backoffice_larp_')]
class LarpIntegrationsSettingsController extends AbstractController
{

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly LarpRepository $larpRepository,
        private readonly LarpIntegrationManager $larpIntegrationManager,
        private readonly IntegrationServiceProvider $integrationServiceProvider,
    )
    {
    }

    #[Route('/{id}/integration-settings', name: 'integration_settings', methods: ['GET', 'POST'])]
    public function integrationsSettings(
        string                    $id,
        LarpIntegrationRepository $larpIntegrationRepository,
        SessionInterface $session
    ): Response
    {
        $larp = $this->larpRepository->find($id);
        if (!$larp) {
            throw $this->createNotFoundException('Larp not found.');
        }
        $integrations = $larpIntegrationRepository->findAllByLarp($id);
        $session->set('integration_file_modal', $integrations[0]->getId()->toRfc4122());
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
            ->redirect([
                Drive::DRIVE_METADATA_READONLY
            ], [
                'access_type' => 'offline',
                'prompt'       => 'consent',
                'redirect_uri' => $this->generateUrl('backoffice_larp_google_drive_connect_check', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);
    }

    #[Route('/integration/connect/googleDrive/check', name: 'google_drive_connect_check')]
    public function connectGoogleCheck(SessionInterface $session): RedirectResponse
    {
        /** @var GoogleClient $client */
        $oauthClient = $this->clientRegistry->getClient(LarpIntegrationProvider::Google->value);
        $larpId = $session->get('current_larp_id');
        $session->set('current_larp_id', true);

        $larpIntegration = $this->larpIntegrationManager->createGoogleDriveIntegration($oauthClient->getAccessToken(), $larpId);
        $session->set('integration_file_modal', $larpIntegration->getId()->toRfc4122());

        $session->remove('current_larp_id');
        return $this->redirectToRoute('backoffice_larp_details', ['id' => $larpId]);
    }

    #[Route('/{id}/integration/{integrationId}/filePermissions', name: 'integration_file_permissions', methods: ['POST'])]
    public function applyFilePermissions(
        string $id,
        string $integrationId,
        Request $request,
        ApplyFilesPermissionsHandler $handler
    ): RedirectResponse
    {
        $rawPermissions = $request->request->all('permissions');
        $command = new ApplyFilesPermissionsCommand($id, $integrationId, $rawPermissions);
        $handler->handle($command);
        return $this->redirectToRoute('backoffice_larp_integration_settings', ['id' => $id]);
    }

    #[Route('/integration/{integrationId}/folder/{folderId}', name: 'integration_get_folder', methods: ['GET'])]
    public function integrationGetFolder(
        Request $request,
        string $folderId,
        string $integrationId,
        LarpIntegrationRepository $larpIntegrationRepository,
    ): JsonResponse
    {
        /** @var LarpIntegration|null $integration */
        $integration = $larpIntegrationRepository->find($integrationId);
        Assert::notNull($integration);
        $service = $this->integrationServiceProvider->getServiceForIntegration($integration->getProvider());
        $items = $service->getFolderContents($integration, $folderId, $request->get('force', false));

        return $this->json($items);
    }

}