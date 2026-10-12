<?php

namespace App\Domain\Integrations\Miro\Credential;

use App\Domain\Integrations\DTO\RefreshedToken;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Service\Credential\OAuthTokenRefresherInterface;
use App\Domain\Integrations\Service\Exceptions\InvalidGrantException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class MiroTokenRefresher implements OAuthTokenRefresherInterface
{
    public const TOKEN_URL = 'https://api.miro.com/v1/oauth/token';

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(OAUTH_MIRO_CLIENT_ID)%')]
        private string $miroClientId,
        #[Autowire('%env(OAUTH_MIRO_CLIENT_SECRET)%')]
        private string $miroClientSecret,
    ) {
    }

    public static function providerKey(): string
    {
        return LarpIntegrationProvider::Miro->value;
    }

    public function refresh(string $refreshToken): RefreshedToken
    {
        $response = $this->httpClient->request('POST', self::TOKEN_URL, [
            'body' => [
                'grant_type' => 'refresh_token',
                'client_id' => $this->miroClientId,
                'client_secret' => $this->miroClientSecret,
                'refresh_token' => $refreshToken,
            ],
        ]);

        $statusCode = $response->getStatusCode();
        $payload = $response->toArray(false);

        if (in_array($statusCode, [400, 401], true)) {
            throw new InvalidGrantException((string) ($payload['message'] ?? $payload['error_description'] ?? $payload['error'] ?? 'Miro rejected the refresh token.'));
        }

        if ($statusCode >= 300 || !isset($payload['access_token'])) {
            throw new \RuntimeException(sprintf('Miro token refresh failed with HTTP %d.', $statusCode));
        }

        return RefreshedToken::fromExpiresIn(
            (string) $payload['access_token'],
            isset($payload['refresh_token']) ? (string) $payload['refresh_token'] : null,
            $payload['expires_in'] ?? null,
        );
    }
}
