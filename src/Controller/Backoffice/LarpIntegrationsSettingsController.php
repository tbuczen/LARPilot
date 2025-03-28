<?php

namespace App\Controller\Backoffice;

use App\Domain\Integrations\UseCase\ApplyFilesPermission\ApplyFilesPermissionsCommand;
use App\Domain\Integrations\UseCase\ApplyFilesPermission\ApplyFilesPermissionsHandler;
use App\Entity\LarpIntegration;
use App\Enum\LarpIntegrationProvider;
use App\Repository\LarpIntegrationRepository;
use App\Repository\LarpRepository;
use App\Security\GoogleAuthenticator;
use App\Service\Integrations\Exceptions\ReAuthenticationNeededException;
use App\Service\Integrations\IntegrationServiceProvider;
use App\Service\Integrations\LarpIntegrationManager;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
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
        private readonly LarpRepository             $larpRepository,
        private readonly LarpIntegrationManager     $larpIntegrationManager,
        private readonly IntegrationServiceProvider $integrationServiceProvider,
        private readonly UrlGeneratorInterface $urlGenerator,
    )
    {
    }

    #[Route('/{id}/integration-settings', name: 'integration_settings', methods: ['GET', 'POST'])]
    public function integrationsSettings(
        string                    $id,
        LarpIntegrationRepository $larpIntegrationRepository,
    ): Response
    {
        $larp = $this->larpRepository->find($id);
        if (!$larp) {
            throw $this->createNotFoundException('Larp not found.');
        }
        $integrations = $larpIntegrationRepository->findAllByLarp($id);
        try {
            $this->larpIntegrationManager->decorateIntegrationsWithClient($integrations);
        } catch (ReAuthenticationNeededException) {
        }
        return $this->render('backoffice/larp/integrationsSettings.html.twig', [
            'larp' => $larp,
            'integrations' => $integrations,
            'availableProviders' => LarpIntegrationProvider::cases(),
        ]);
    }

    #[Route('/{id}/integration/connect/{provider}', name: 'connect_integration')]
    public function connectIntegration(
        string                 $id,
        string                 $provider,
        LarpRepository         $larpRepository,
        LarpIntegrationManager $integrationManager,
        SessionInterface $session,
    ): Response
    {
        $session->set('current_larp_id', $id);

        $larp = $larpRepository->find($id);
        $enum = LarpIntegrationProvider::from($provider);

        $integrationService = $integrationManager->getIntegrationServiceByProvider($enum);

        return $integrationService->connect($larp);
    }

    #[Route('/integration/connect/{provider}/check', name: 'connect_integration_check')]
    public function connectIntegrationCheck(
        string $provider,
        SessionInterface $session,
        ClientRegistry $clientRegistry,
        IntegrationServiceProvider $integrationServiceProvider,
    ): RedirectResponse {
        $larpId = $session->get('current_larp_id');
        $client = $clientRegistry->getClient($provider);

        $accessToken = $client->getAccessToken([
            'redirect_uri' => $this->urlGenerator->generate(
                'backoffice_larp_connect_integration_check',
                ['provider' => LarpIntegrationProvider::Google->value],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);
        $resourceOwner = $client->fetchUserFromToken($accessToken);

        $providerEnum = LarpIntegrationProvider::from($provider);
        $integrationService = $integrationServiceProvider->getServiceForIntegration($providerEnum);

        $integrationService->finalizeConnection($larpId, $accessToken, $resourceOwner);

        $session->remove('current_larp_id');

        return $this->redirectToRoute('backoffice_larp_integration_settings', ['id' => $larpId]);
    }

    #[Route('/{id}/integration/{integrationId}/filePermissions', name: 'integration_file_permissions', methods: ['POST'])]
    public function applyFilePermissions(
        string                       $id,
        string                       $integrationId,
        Request                      $request,
        ApplyFilesPermissionsHandler $handler
    ): RedirectResponse
    {
        $selectedFilesJson = $request->request->get('selectedFiles', '[]');
        $files = json_decode($selectedFilesJson, true) ?? [];
        $command = new ApplyFilesPermissionsCommand($integrationId, $files);
        $handler->handle($command);
        return $this->redirectToRoute('backoffice_larp_integration_settings', ['id' => $id]);
    }

    #[Route('/integration/{integrationId}/folder/{folderId}', name: 'integration_get_folder', methods: ['GET'])]
    public function integrationGetFolder(
        Request                   $request,
        string                    $folderId,
        string                    $integrationId,
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