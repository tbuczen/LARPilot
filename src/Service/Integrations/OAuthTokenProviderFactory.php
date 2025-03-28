<?php

namespace App\Service\Integrations;

use App\Entity\LarpIntegration;
use App\Enum\LarpIntegrationProvider;
use App\Service\Integrations\Google\GoogleOAuthTokenProvider;

readonly class OAuthTokenProviderFactory
{

    public function __construct(
        private GoogleOAuthTokenProvider $googleOAuthTokenProvider
    ) {}

    public function getProviderForIntegration(LarpIntegration $integration): OAuthTokenProviderInterface
    {
        if ($integration->getProvider() === LarpIntegrationProvider::Google) {
            return $this->googleOAuthTokenProvider;
        }

        throw new \InvalidArgumentException("No OAuth provider found for integration:". $integration->getId()->toRfc4122());
    }
}