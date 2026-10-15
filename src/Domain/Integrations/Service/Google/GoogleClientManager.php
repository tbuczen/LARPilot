<?php

namespace App\Domain\Integrations\Service\Google;

use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Credential\OAuthCredentialProvider;
use Google\Client;
use Google\Service\Drive;

readonly class GoogleClientManager implements GoogleClientFactoryInterface
{
    public function __construct(
        private string                   $serviceAccountJsonPath,
        private GoogleOAuthClientFactory $oauthClientFactory,
        private OAuthCredentialProvider  $oauthCredentialProvider,
    ) {
    }

    public function createServiceAccountClient(): Client
    {
        $client = new Client();
        $client->setAuthConfig($this->serviceAccountJsonPath);
        $client->addScope([Drive::DRIVE, Drive::DRIVE_FILE]);
        return $client;
    }

    public function getServiceAccountEmail(): string
    {
        $json = json_decode(file_get_contents($this->serviceAccountJsonPath), true);
        return $json['client_email'] ?? throw new \RuntimeException('Missing client_email in service account JSON');
    }

    public function getClientForIntegration(LarpIntegration $integration): Client
    {
        $accessToken = $this->oauthCredentialProvider->accessToken($integration);
        $expiresAt = $integration->getExpiresAt()?->getTimestamp();
        $now = time();

        $client = $this->oauthClientFactory->create();
        $client->setAccessToken([
            'access_token' => $accessToken,
            'created' => $now,
            'expires_in' => $expiresAt === null ? 3600 : max(0, $expiresAt - $now),
        ]);

        return $client;
    }
}
