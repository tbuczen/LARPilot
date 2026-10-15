<?php

namespace App\Domain\Integrations\Miro\OAuth;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Service\Exceptions\IntegrationConnectionFailedException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

readonly class KnpuMiroOAuthGateway implements MiroOAuthGatewayInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
    ) {
    }

    public function authorizationUrl(string $state, string $redirectUri): string
    {
        return $this->oauthProvider()->getAuthorizationUrl([
            'state' => $state,
            'redirect_uri' => $redirectUri,
        ]);
    }

    public function exchangeAuthorizationCode(string $authorizationCode, string $redirectUri): MiroAuthorization
    {
        try {
            $accessToken = $this->oauthProvider()->getAccessToken('authorization_code', [
                'code' => $authorizationCode,
                'redirect_uri' => $redirectUri,
            ]);
        } catch (IdentityProviderException $exception) {
            throw new IntegrationConnectionFailedException($exception->getMessage(), 0, $exception);
        }

        return MiroAuthorization::fromAccessToken($accessToken);
    }

    private function oauthProvider(): AbstractProvider
    {
        return $this->clientRegistry->getClient(LarpIntegrationProvider::Miro->value)->getOAuth2Provider();
    }
}
