<?php

namespace App\Domain\Integrations\Service\Google;

use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Service\OAuthTokenProviderInterface;

readonly class GoogleOAuthTokenProvider implements OAuthTokenProviderInterface
{
    public function __construct(
        private LarpIntegrationRepository $integrationRepository,
        private GoogleClientManager       $googleClientManager
    ) {
    }

    public function getTokenForIntegration(string $integrationId): ?string
    {
        $integration = $this->integrationRepository->find($integrationId);

        if (!$integration) {
            throw new \InvalidArgumentException("Integration with ID $integrationId not found.");
        }

        $client = $this->googleClientManager->getClientForIntegration($integration);

        return $client->getAccessToken()['access_token'] ?? null;
    }
}
