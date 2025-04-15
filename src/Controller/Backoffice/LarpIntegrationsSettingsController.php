<?php

namespace App\Controller\Backoffice;

use App\Domain\Integrations\UseCase\ApplyFilesPermission\ApplyFilesPermissionsCommand;
use App\Domain\Integrations\UseCase\ApplyFilesPermission\ApplyFilesPermissionsHandler;
use App\Entity\Larp;
use App\Enum\LarpIntegrationProvider;
use App\Repository\LarpIntegrationRepository;
use App\Repository\LarpRepository;
use App\Service\Integrations\Exceptions\ReAuthenticationNeededException;
use App\Service\Integrations\IntegrationServiceProvider;
use App\Service\Integrations\LarpIntegrationManager;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
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
        private readonly LarpIntegrationManager $larpIntegrationManager,
        private readonly UrlGeneratorInterface  $urlGenerator,
    )
    {
    }

    #[Route('/{larp}/integration-settings', name: 'integration_settings', methods: ['GET', 'POST'])]
    public function integrationsSettings(
        Larp                      $larp,
        LarpIntegrationRepository $larpIntegrationRepository,
    ): Response
    {
        $integrations = $larpIntegrationRepository->findAllByLarp($larp->getId()->toRfc4122());
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
        SessionInterface       $session,
    ): Response
    {
        $session->set('current_larp_id', $id);
        $larp = $larpRepository->find($id);
        $integrationService = $integrationManager->getIntegrationServiceByProvider(LarpIntegrationProvider::from($provider));

        return $integrationService->connect($larp);
    }

    #[Route('/integration/connect/{provider}/check', name: 'connect_integration_check')]
    public function connectIntegrationCheck(
        string                     $provider,
        SessionInterface           $session,
        ClientRegistry             $clientRegistry,
        IntegrationServiceProvider $integrationServiceProvider,
    ): RedirectResponse
    {
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

        return $this->redirectToRoute('backoffice_larp_integration_settings', ['larp' => $larpId]);
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
        return $this->redirectToRoute('backoffice_larp_integration_settings', ['larp' => $id]);
    }

}