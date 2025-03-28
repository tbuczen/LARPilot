<?php

namespace App\Service\Integrations;

use App\Entity\LarpIntegration;
use App\Enum\LarpIntegrationProvider;
use App\Repository\LarpIntegrationRepository;
use App\Service\Integrations\Exceptions\ReAuthenticationNeededException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;

final readonly class LarpIntegrationManager
{

    public function __construct(
        private ClientRegistry            $clientRegistry,
        private LarpIntegrationRepository $larpIntegrationRepository,
        private IntegrationServiceProvider $integrationServiceProvider,
        private OAuthTokenProviderFactory $oauthTokenProviderFactory,
    ) {
    }

    /**
     * @throws ReAuthenticationNeededException
     */
    public function decorateIntegrationsWithClient(array $integrations): void
    {
        /** @var LarpIntegration $integration */
        foreach ($integrations as $integration) {
            $tokenProvider = $this->oauthTokenProviderFactory->getProviderForIntegration($integration);
            $accessToken = $tokenProvider->getTokenForIntegration($integration->getId());
            $oauthClient = $this->clientRegistry->getClient($integration->getProvider()->value);
            $integration->setClient($oauthClient);
            $integration->setAccessToken($accessToken);
            $integration->getSharedFiles()->count();
        }
    }

    public function getServiceByIntegrationId(string $integrationId): IntegrationServiceInterface
    {
        $integration = $this->larpIntegrationRepository->find($integrationId);

        if (!$integration) {
            throw new \InvalidArgumentException("Integration with ID $integrationId not found.");
        }

        return $this->getIntegrationServiceByProvider($integration->getProvider());
    }

    public function getIntegrationServiceByProvider(LarpIntegrationProvider $provider): IntegrationServiceInterface
    {
        return $this->integrationServiceProvider->getServiceForIntegration($provider);
    }

}
