<?php

namespace App\Domain\Integrations\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\LarpRepository;
use App\Domain\Integrations\DTO\ConnectionCallback;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Security\IntegrationOwnershipGuard;
use App\Domain\Integrations\Security\Voter\IntegrationSettingsVoter;
use App\Domain\Integrations\Service\Capability\AccessTokenProviderInterface;
use App\Domain\Integrations\Service\Capability\ConnectableIntegrationInterface;
use App\Domain\Integrations\Service\Connect\ConnectStateManager;
use App\Domain\Integrations\Service\Exceptions\IntegrationConnectionFailedException;
use App\Domain\Integrations\Service\Exceptions\InvalidConnectStateException;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use App\Domain\Integrations\Service\Exceptions\UnsupportedCapability;
use App\Domain\Integrations\Service\IntegrationRegistry;
use App\Domain\Integrations\UseCase\ApplyFilesPermission\ApplyFilesPermissionsCommand;
use App\Domain\Integrations\UseCase\ApplyFilesPermission\ApplyFilesPermissionsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/larp', name: 'backoffice_larp_')]
class IntegrationsSettingsController extends AbstractController
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly IntegrationOwnershipGuard $integrationOwnershipGuard,
        private readonly IntegrationRegistry $integrationRegistry,
        private readonly TranslatorInterface $translator,
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
            'availableProviders' => $this->integrationRegistry->providersSupporting(ConnectableIntegrationInterface::class),
        ]);
    }

    #[Route('/{larp}/integration/connect/{provider}', name: 'connect_integration', methods: ['GET'])]
    public function connectIntegration(
        Larp                    $larp,
        LarpIntegrationProvider $provider,
        ConnectStateManager     $connectStateManager,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);

        $connectableIntegration = $this->connectableIntegration($provider);
        $state = $connectStateManager->issue($larp, $provider);

        return new RedirectResponse($connectableIntegration->authorizationUrl($larp, $state, $this->callbackUrl($provider)));
    }

    #[Route('/integration/connect/{provider}/check', name: 'connect_integration_check', methods: ['GET'])]
    public function connectIntegrationCheck(
        LarpIntegrationProvider $provider,
        Request                 $request,
        ConnectStateManager     $connectStateManager,
        LarpRepository          $larpRepository,
    ): RedirectResponse {
        try {
            $larpId = $connectStateManager->consume($request->query->getString('state'), $provider);
        } catch (InvalidConnectStateException) {
            throw new BadRequestHttpException('Invalid or expired integration connect state.');
        }

        $larp = $larpRepository->find($larpId);
        if (!$larp instanceof Larp) {
            throw $this->createNotFoundException('LARP not found.');
        }

        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $connectableIntegration = $this->connectableIntegration($provider);

        $callback = new ConnectionCallback($this->callbackUrl($provider), $request->query->all());
        $settingsRedirect = $this->redirectToRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()->toRfc4122()]);

        if ($callback->providerError() !== null || $callback->authorizationCode() === null) {
            $this->addFlash('warning', $this->translator->trans('larp.integration.connect_cancelled', ['%provider%' => $provider->getLabel()]));

            return $settingsRedirect;
        }

        $connectingUser = $this->getUser();
        if (!$connectingUser instanceof User) {
            throw $this->createAccessDeniedException();
        }

        try {
            $connectableIntegration->completeConnection($larp, $callback, $connectingUser);
        } catch (IntegrationConnectionFailedException) {
            $this->addFlash('danger', $this->translator->trans('larp.integration.connect_failed', ['%provider%' => $provider->getLabel()]));

            return $settingsRedirect;
        }

        $this->addFlash('success', $this->translator->trans('larp.integration.connect_succeeded', ['%provider%' => $provider->getLabel()]));

        return $settingsRedirect;
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
    ): JsonResponse {
        $this->denyAccessUnlessGranted(IntegrationSettingsVoter::MODIFY, $larp);
        $this->integrationOwnershipGuard->assertIntegrationBelongsToLarp($integration, $larp);

        if (!$this->isCsrfTokenValid('integration_picker_token_' . $integration->getId()->toRfc4122(), $request->headers->get('X-CSRF-Token', ''))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        try {
            $accessTokenProvider = $this->integrationRegistry->capability($integration, AccessTokenProviderInterface::class);
        } catch (UnsupportedCapability) {
            throw $this->createNotFoundException();
        }

        try {
            $accessToken = $accessTokenProvider->accessToken($integration);
        } catch (ReAuthenticationNeededException) {
            return $this->noStore(new JsonResponse(['error' => 'reauthentication_required'], Response::HTTP_CONFLICT));
        }

        return $this->noStore(new JsonResponse(['accessToken' => $accessToken]));
    }

    private function connectableIntegration(LarpIntegrationProvider $provider): ConnectableIntegrationInterface
    {
        try {
            return $this->integrationRegistry->providerCapability($provider, ConnectableIntegrationInterface::class);
        } catch (UnsupportedCapability) {
            throw $this->createNotFoundException();
        }
    }

    private function callbackUrl(LarpIntegrationProvider $provider): string
    {
        return $this->urlGenerator->generate(
            'backoffice_larp_connect_integration_check',
            ['provider' => $provider->value],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function noStore(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
