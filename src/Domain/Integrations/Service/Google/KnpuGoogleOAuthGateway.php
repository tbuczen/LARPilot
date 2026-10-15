<?php

namespace App\Domain\Integrations\Service\Google;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;

readonly class KnpuGoogleOAuthGateway implements GoogleOAuthGatewayInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
    ) {
    }

    public function authorizationUrl(array $scopes, string $state, string $redirectUri): string
    {
        return $this->oauthProvider()->getAuthorizationUrl([
            'scope' => $scopes,
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'redirect_uri' => $redirectUri,
        ]);
    }

    public function exchangeAuthorizationCode(string $authorizationCode, string $redirectUri): GoogleAuthorization
    {
        $oauthProvider = $this->oauthProvider();
        $accessToken = $oauthProvider->getAccessToken('authorization_code', [
            'code' => $authorizationCode,
            'redirect_uri' => $redirectUri,
        ]);

        $resourceOwner = $accessToken instanceof AccessToken ? $oauthProvider->getResourceOwner($accessToken) : null;
        $accountEmail = $resourceOwner instanceof GoogleUser ? $resourceOwner->getEmail() : null;

        return new GoogleAuthorization($accessToken, $accountEmail);
    }

    private function oauthProvider(): AbstractProvider
    {
        return $this->clientRegistry->getClient(LarpIntegrationProvider::Google->value)->getOAuth2Provider();
    }
}
