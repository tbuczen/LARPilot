<?php

namespace App\Service\Integrations\Google;

use App\Entity\LarpIntegration;
use App\Service\Integrations\Exceptions\ReAuthenticationNeededException;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client;
use Google\Service\Drive;

readonly class GoogleClientManager
{
    public function __construct(
        private string                 $googleClientId,
        private string                 $googleClientSecret,
        private string                 $serviceAccountJsonPath,
        private EntityManagerInterface $entityManager,
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

    /**
     * @param LarpIntegration $integration
     * @return Client
     *
     * @throws ReAuthenticationNeededException if token refresh fails.
     */
    public function getClientForIntegration(LarpIntegration $integration): Client
    {
        // Build the stored token array from your integration entity.
        $storedToken = [
            'access_token' => $integration->getAccessToken(),
            'expires' => $integration->getExpiresAt()->getTimestamp(),
            'refresh_token' => $integration->getRefreshToken(),
        ];

        $client = new Client([
            'client_id' => $this->googleClientId,
            'client_secret' => $this->googleClientSecret,
        ]);
        $client->setAccessToken($storedToken);

        if ($client->isAccessTokenExpired() && (isset($storedToken['refresh_token']) && ($storedToken['refresh_token'] !== '' && $storedToken['refresh_token'] !== '0'))) {
            $this->refreshToken($client, $storedToken['refresh_token'], $integration);
        }

        return $client;
    }

    public function refreshToken(Client $client, $refreshToken, LarpIntegration $integration): void
    {
        $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
        if (isset($newToken['error'])) {
            throw new ReAuthenticationNeededException($integration->getId()->toRfc4122());
        }
        if (!isset($newToken['refresh_token'])) {
            $newToken['refresh_token'] = $refreshToken;
        }
        $integration->setAccessToken($newToken['access_token']);
        $integration->setRefreshToken($newToken['refresh_token']);
        $expiresAt = (new \DateTime())->modify('+' . $newToken['expires_in'] . ' seconds');
        $integration->setExpiresAt($expiresAt);
        $this->entityManager->persist($integration);
        $this->entityManager->flush();

        $client->setAccessToken($newToken);
    }
}
