<?php

namespace App\Domain\Integrations\Service\Google;

use App\Domain\Integrations\DTO\RefreshedToken;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Service\Credential\OAuthTokenRefresherInterface;
use App\Domain\Integrations\Service\Exceptions\InvalidGrantException;

readonly class GoogleTokenRefresher implements OAuthTokenRefresherInterface
{
    private const INVALID_GRANT = 'invalid_grant';

    public function __construct(
        private GoogleOAuthClientFactory $oauthClientFactory,
    ) {
    }

    public static function providerKey(): string
    {
        return LarpIntegrationProvider::Google->value;
    }

    public function refresh(string $refreshToken): RefreshedToken
    {
        $refreshedToken = $this->oauthClientFactory->create()->fetchAccessTokenWithRefreshToken($refreshToken);

        $error = $refreshedToken['error'] ?? null;
        if ($error === self::INVALID_GRANT) {
            throw new InvalidGrantException($refreshedToken['error_description'] ?? self::INVALID_GRANT);
        }

        if ($error !== null || !isset($refreshedToken['access_token'])) {
            throw new \RuntimeException(sprintf('Google token refresh failed: %s', $refreshedToken['error_description'] ?? $error ?? 'no access token returned'));
        }

        return RefreshedToken::fromExpiresIn(
            $refreshedToken['access_token'],
            $refreshedToken['refresh_token'] ?? null,
            $refreshedToken['expires_in'] ?? null,
        );
    }
}
