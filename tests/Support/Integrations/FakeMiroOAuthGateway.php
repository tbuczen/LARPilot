<?php

declare(strict_types=1);

namespace Tests\Support\Integrations;

use App\Domain\Integrations\Miro\OAuth\MiroAuthorization;
use App\Domain\Integrations\Miro\OAuth\MiroOAuthGatewayInterface;
use App\Domain\Integrations\Miro\OAuth\MiroOAuthProvider;
use App\Domain\Integrations\Service\Exceptions\IntegrationConnectionFailedException;

final class FakeMiroOAuthGateway implements MiroOAuthGatewayInterface
{
    public const AUTHORIZATION_ENDPOINT = MiroOAuthProvider::AUTHORIZE_URL;
    public const TEAM_ID = '3458764517517818867';
    public const USER_ID = '3458764517517852417';
    public const REJECTED_CODE = 'rejected-code';

    public static function accessTokenFor(string $authorizationCode): string
    {
        return 'miro-access-token-for-' . $authorizationCode;
    }

    public static function refreshTokenFor(string $authorizationCode): string
    {
        return 'miro-refresh-token-for-' . $authorizationCode;
    }

    public function authorizationUrl(string $state, string $redirectUri): string
    {
        return self::AUTHORIZATION_ENDPOINT . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => 'test-miro-client-id',
            'scope' => MiroOAuthProvider::BOARDS_READ_SCOPE,
            'state' => $state,
            'redirect_uri' => $redirectUri,
        ]);
    }

    public function exchangeAuthorizationCode(string $authorizationCode, string $redirectUri): MiroAuthorization
    {
        if ($authorizationCode === self::REJECTED_CODE) {
            throw new IntegrationConnectionFailedException('invalid_grant');
        }

        return new MiroAuthorization(
            self::accessTokenFor($authorizationCode),
            self::refreshTokenFor($authorizationCode),
            new \DateTimeImmutable('+3599 seconds'),
            self::TEAM_ID,
            self::USER_ID,
            MiroOAuthProvider::BOARDS_READ_SCOPE,
        );
    }
}
