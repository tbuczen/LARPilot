<?php

namespace App\Domain\Integrations\Service\Google;

use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client;
use Google\Service\Drive;

readonly class GoogleClientManager implements GoogleClientFactoryInterface
{
    public function __construct(
        private string                   $serviceAccountJsonPath,
        private EntityManagerInterface   $entityManager,
        private GoogleOAuthClientFactory $oauthClientFactory,
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
     * @throws ReAuthenticationNeededException
     */
    public function getClientForIntegration(LarpIntegration $integration): Client
    {
        $client = $this->oauthClientFactory->create();
        $client->setAccessToken($this->buildStoredToken($integration));

        if (!$client->isAccessTokenExpired()) {
            return $client;
        }

        $this->refreshToken($client, $integration);

        return $client;
    }

    /**
     * @throws ReAuthenticationNeededException
     */
    public function refreshToken(Client $client, LarpIntegration $integration): void
    {
        $refreshToken = $integration->getRefreshToken();
        if ($refreshToken === null || $refreshToken === '') {
            $this->failRefresh($integration, 'Missing refresh token.');
        }

        try {
            $refreshedToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
        } catch (\Throwable $exception) {
            $this->failRefresh($integration, $exception->getMessage());
        }

        if (isset($refreshedToken['error']) || !isset($refreshedToken['access_token'])) {
            $this->failRefresh($integration, $refreshedToken['error_description'] ?? $refreshedToken['error'] ?? 'Token refresh failed.');
        }

        $refreshedToken['refresh_token'] ??= $refreshToken;
        $expiresIn = (int) ($refreshedToken['expires_in'] ?? 0);

        $integration->setAccessToken($refreshedToken['access_token']);
        $integration->setRefreshToken($refreshedToken['refresh_token']);
        $integration->setExpiresAt(new \DateTimeImmutable('+' . $expiresIn . ' seconds'));
        $integration->markActive();
        $this->entityManager->persist($integration);
        $this->entityManager->flush();

        $client->setAccessToken($this->buildStoredToken($integration));
    }

    /**
     * @return array{access_token: string, refresh_token: string|null, created: int, expires_in: int}
     */
    private function buildStoredToken(LarpIntegration $integration): array
    {
        $now = time();
        $expiresAt = $integration->getExpiresAt()?->getTimestamp() ?? $now;

        return [
            'access_token' => $integration->getAccessToken() ?? '',
            'refresh_token' => $integration->getRefreshToken(),
            'created' => $now,
            'expires_in' => max(0, $expiresAt - $now),
        ];
    }

    /**
     * @throws ReAuthenticationNeededException
     */
    private function failRefresh(LarpIntegration $integration, string $reason): never
    {
        $integration->markNeedsReauth($reason);
        $this->entityManager->persist($integration);
        $this->entityManager->flush();

        throw new ReAuthenticationNeededException($integration->getId()->toRfc4122());
    }
}
