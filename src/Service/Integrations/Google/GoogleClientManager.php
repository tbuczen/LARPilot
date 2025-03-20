<?php
namespace App\Service\Integrations\Google;

use App\Entity\LarpIntegration;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client;
use Exception;

readonly class GoogleClientManager
{
    public function __construct(
        private string                 $googleClientId,
        private string                 $googleClientSecret,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Returns a configured Google Client for a given integration.
     *
     * This method checks if the token is expired and, if so,
     * refreshes it and updates the LarpIntegration entity.
     *
     * @param LarpIntegration $integration
     * @return Client
     *
     * @throws Exception if token refresh fails.
     */
    public function getClientForIntegration(LarpIntegration $integration): Client
    {
        // Build the stored token array from your integration entity.
        $storedToken = [
            'access_token'  => $integration->getAccessToken(),
            'expires'       => $integration->getExpiresAt()->getTimestamp(),
            'refresh_token' => $integration->getRefreshToken(),
        ];

        $client = new Client([
            'client_id'     => $this->googleClientId,
            'client_secret' => $this->googleClientSecret,
        ]);
        $client->setAccessToken($storedToken);

        if ($client->isAccessTokenExpired() && !empty($storedToken['refresh_token'])) {
            $this->refreshToken($client, $storedToken['refresh_token'], $integration);
        }

        return $client;
    }

    public function refreshToken(Client $client, $refresh_token, LarpIntegration $integration): void
    {
        $newToken = $client->fetchAccessTokenWithRefreshToken($refresh_token);
        if (isset($newToken['error'])) {
            throw new Exception('Error refreshing Google token: ' . $newToken['error']);
        }
        // Ensure the refresh token remains if it's not returned in the new token.
        if (!isset($newToken['refresh_token'])) {
            $newToken['refresh_token'] = $refresh_token;
        }
        // Update the integration entity.
        $integration->setAccessToken($newToken['access_token']);
        $integration->setRefreshToken($newToken['refresh_token']);
        $expiresAt = (new \DateTime())->modify('+' . $newToken['expires_in'] . ' seconds');
        $integration->setExpiresAt($expiresAt);
        $this->entityManager->persist($integration);
        $this->entityManager->flush();

        // Update the client with the new token.
        $client->setAccessToken($newToken);
    }
}
