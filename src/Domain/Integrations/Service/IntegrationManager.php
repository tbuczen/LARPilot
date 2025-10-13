<?php

namespace App\Domain\Integrations\Service;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\Uid\Uuid;

final readonly class IntegrationManager
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

    public function getService(LarpIntegration|string|Uuid|LarpIntegrationProvider $input): IntegrationServiceInterface
    {
        $provider = match (true) {
            is_string($input), $input instanceof Uuid => $this->larpIntegrationRepository->find($input)?->getProvider(),
            $input instanceof LarpIntegrationProvider => $input,
            $input instanceof LarpIntegration => $input->getProvider(),
            default => throw new \InvalidArgumentException("Invalid type for integrationOrId: " . get_debug_type($input)),
        };

        if (!$provider) {
            throw new \InvalidArgumentException("Integration not found for given ID.");
        }

        return $this->integrationServiceProvider->getServiceForIntegration($provider);
    }
}
