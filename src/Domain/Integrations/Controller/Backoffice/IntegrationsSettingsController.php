<?php

namespace App\Domain\Integrations\Controller\Backoffice;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\LarpRepository;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Security\IntegrationOwnershipGuard;
use App\Domain\Integrations\Security\Voter\IntegrationSettingsVoter;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use App\Domain\Integrations\Service\IntegrationManager;
use App\Domain\Integrations\Service\IntegrationServiceProvider;
use App\Domain\Integrations\Service\OAuthTokenProviderFactory;
use App\Domain\Integrations\UseCase\ApplyFilesPermission\ApplyFilesPermissionsCommand;
use App\Domain\Integrations\UseCase\ApplyFilesPermission\ApplyFilesPermissionsHandler;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/larp', name: 'backoffice_larp_')]
class IntegrationsSettingsController extends AbstractController
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly IntegrationOwnershipGuard $integrationOwnershipGuard,
    ) {
    }

    #[Route('/{larp}/integration-settings', name: 'integration_settings', methods: ['GET'])]
    public function integrationsSettings(
        Larp                      $larp,
        LarpIntegrationRepository $larpIntegrationRepository,
    ): Response {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);

        $integrations = $larpIntegrationRepository->findAllByLarp($larp);
        return $this->render('backoffice/larp/integrationsSettings.html.twig', [
            'larp' => $larp,
            'integrations' => $integrations,
            'availableProviders' => LarpIntegrationProvider::cases(),
        ]);
    }

    #[Route('/{larp}/integration/connect/{provider}', name: 'connect_integration')]
    public function connectIntegration(
        Larp                  $larp,
        LarpIntegrationProvider $provider,
        IntegrationManager      $integrationManager,
        SessionInterface        $session,
    ): Response {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);

        $session->set('current_larp_id', $larp->getId());
        $integrationService = $integrationManager->getService($provider);

        return $integrationService->connect($larp);
    }

    #[Route('/integration/connect/{provider}/check', name: 'connect_integration_check')]
    public function connectIntegrationCheck(
        string                       $provider,
        SessionInterface             $session,
        ClientRegistry               $clientRegistry,
        IntegrationServiceProvider   $integrationServiceProvider,
        LarpRepository               $larpRepository,
    ): RedirectResponse {
        $larpId = $session->get('current_larp_id');

        if (!$larpId) {
            throw $this->createAccessDeniedException('No LARP ID in session.');
        }

        $larp = $larpRepository->find($larpId);
        if (!$larp instanceof Larp) {
            $session->remove('current_larp_id');
            throw $this->createNotFoundException('LARP not found.');
        }

        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);

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

    #[Route('/{larp}/integration/{integrationId}/filePermissions', name: 'integration_file_permissions', methods: ['POST'])]
    public function applyFilePermissions(
        Larp                       $larp,
        string                       $integrationId,
        Request                      $request,
        ApplyFilesPermissionsHandler $handler
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $integration = $this->integrationOwnershipGuard->findIntegrationOfLarp($integrationId, $larp);

        if (!$this->isCsrfTokenValid('integration_file_permissions_' . $integration->getId()->toRfc4122(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $selectedFilesJson = $request->request->getString('selectedFiles', '[]');
        $files = json_decode($selectedFilesJson, true);
        if (!is_array($files)) {
            $files = [];
        }

        $command = new ApplyFilesPermissionsCommand($integration->getId()->toRfc4122(), $files);
        $handler->handle($command);
        return $this->redirectToRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()->toRfc4122()]);
    }


    #[Route('/{larp}/integration/{integration}/externalResourceMapping/{sharedFile}', name: 'external_resource_mapping', methods: ['GET'])]
    public function externalResourceMapping(
        Larp            $larp,
        LarpIntegration $integration,
        ?SharedFile     $sharedFile = null,
    ): Response {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $this->integrationOwnershipGuard->assertIntegrationBelongsToLarp($integration, $larp);

        if (!$sharedFile instanceof SharedFile) {
            $files = $integration->getSharedFiles()->toArray();
        } else {
            $this->integrationOwnershipGuard->assertSharedFileBelongsToIntegration($sharedFile, $integration);
            $files = [$sharedFile];
        }

        return $this->render('backoffice/larp/integrations/externalResourceMapping.html.twig', ['larp' => $larp, 'files' => $files]);
    }

    #[Route('/{larp}/integration/{integration}/picker-token', name: 'integration_picker_token', methods: ['POST'])]
    public function pickerToken(
        Larp                      $larp,
        LarpIntegration           $integration,
        Request                   $request,
        OAuthTokenProviderFactory $oauthTokenProviderFactory,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $this->integrationOwnershipGuard->assertIntegrationBelongsToLarp($integration, $larp);

        if (!$this->isCsrfTokenValid('integration_picker_token_' . $integration->getId()->toRfc4122(), $request->headers->get('X-CSRF-Token', ''))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $tokenProvider = $oauthTokenProviderFactory->findProviderForIntegration($integration);
        if ($tokenProvider === null) {
            throw $this->createNotFoundException();
        }

        try {
            $accessToken = $tokenProvider->getTokenForIntegration($integration->getId()->toRfc4122());
        } catch (ReAuthenticationNeededException|\LogicException) {
            return $this->noStore(new JsonResponse(['error' => 'reauthentication_required'], Response::HTTP_CONFLICT));
        }

        if ($accessToken === null) {
            return $this->noStore(new JsonResponse(['error' => 'reauthentication_required'], Response::HTTP_CONFLICT));
        }

        return $this->noStore(new JsonResponse(['accessToken' => $accessToken]));
    }

    private function noStore(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
