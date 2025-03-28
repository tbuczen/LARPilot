<?php

namespace App\Service\Integrations\Google;

use App\Entity\LarpIntegration;
use App\Repository\LarpIntegrationRepository;
use App\Service\Integrations\Exceptions\ReAuthenticationNeededException;
use App\Service\Integrations\OAuthTokenProviderInterface;

readonly class GoogleOAuthTokenProvider implements OAuthTokenProviderInterface
{
    public function __construct(
        private LarpIntegrationRepository $integrationRepository,
        private GoogleClientManager       $googleClientManager
    ) {}

    public function getTokenForIntegration(string $integrationId): ?string
    {
        /** @var LarpIntegration $integration */
        $integration = $this->integrationRepository->find($integrationId);

        if (!$integration) {
            throw new \InvalidArgumentException("Integration with ID $integrationId not found.");
        }

        $client = $this->googleClientManager->getClientForIntegration($integration);

        if ($client->isAccessTokenExpired()) {
            $token = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

            if (isset($token['error']) && $token['error_description'] === 'Token has been expired or revoked.') {
                throw new ReAuthenticationNeededException($integration->getId()->toRfc4122());
            }
            $integration->setAccessToken($token['access_token']);
            $integration->setRefreshToken($token['refresh_token']);
            $integration->setExpiresAt((new \DateTime())->setTimestamp($token['expires_at']) );
            $this->integrationRepository->save($integration);
        }

        return $client->getAccessToken()['access_token'] ?? null;
    }
}