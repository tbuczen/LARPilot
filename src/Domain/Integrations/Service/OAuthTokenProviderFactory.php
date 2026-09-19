<?php

namespace App\Domain\Integrations\Service;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Google\GoogleOAuthTokenProvider;

readonly class OAuthTokenProviderFactory
{
    public function __construct(
        private GoogleOAuthTokenProvider $googleOAuthTokenProvider
    ) {
    }

    public function getProviderForIntegration(LarpIntegration $integration): OAuthTokenProviderInterface
    {
        $tokenProvider = $this->findProviderForIntegration($integration);

        if (!$tokenProvider instanceof OAuthTokenProviderInterface) {
            throw new \LogicException("No OAuth provider found for integration:" . $integration->getId()->toRfc4122());
        }

        return $tokenProvider;
    }

    public function findProviderForIntegration(LarpIntegration $integration): ?OAuthTokenProviderInterface
    {
        if ($integration->getProvider() === LarpIntegrationProvider::Google) {
            return $this->googleOAuthTokenProvider;
        }

        return null;
    }
}
