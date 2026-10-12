<?php

declare(strict_types=1);

namespace Tests\Support\Integrations;

use App\Domain\Integrations\Service\Google\GoogleAuthorization;
use App\Domain\Integrations\Service\Google\GoogleOAuthGatewayInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;

final class FakeGoogleOAuthGateway implements GoogleOAuthGatewayInterface
{
    public const AUTHORIZATION_ENDPOINT = 'https://accounts.google.test/o/oauth2/v2/auth';
    public const ACCOUNT_EMAIL = 'organizer@example.com';
    public const REJECTED_CODE = 'rejected-code';
    public const CODE_WITHOUT_REFRESH_TOKEN = 'code-without-refresh-token';

    public static function accessTokenFor(string $authorizationCode): string
    {
        return 'access-token-for-' . $authorizationCode;
    }

    public static function refreshTokenFor(string $authorizationCode): string
    {
        return 'refresh-token-for-' . $authorizationCode;
    }

    public function authorizationUrl(array $scopes, string $state, string $redirectUri): string
    {
        return self::AUTHORIZATION_ENDPOINT . '?' . http_build_query([
            'scope' => implode(' ', $scopes),
            'state' => $state,
            'redirect_uri' => $redirectUri,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);
    }

    public function exchangeAuthorizationCode(string $authorizationCode, string $redirectUri): GoogleAuthorization
    {
        if ($authorizationCode === self::REJECTED_CODE) {
            throw new IdentityProviderException('invalid_grant', 400, ['error' => 'invalid_grant']);
        }

        $tokenValues = [
            'access_token' => self::accessTokenFor($authorizationCode),
            'refresh_token' => self::refreshTokenFor($authorizationCode),
            'expires_in' => 3600,
            'scope' => 'https://www.googleapis.com/auth/drive email',
        ];
        if ($authorizationCode === self::CODE_WITHOUT_REFRESH_TOKEN) {
            unset($tokenValues['refresh_token']);
        }

        return new GoogleAuthorization(new AccessToken($tokenValues), self::ACCOUNT_EMAIL);
    }
}
