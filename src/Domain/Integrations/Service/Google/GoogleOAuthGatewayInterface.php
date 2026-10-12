<?php

namespace App\Domain\Integrations\Service\Google;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

interface GoogleOAuthGatewayInterface
{
    /**
     * @param list<string> $scopes
     */
    public function authorizationUrl(array $scopes, string $state, string $redirectUri): string;

    /**
     * @throws IdentityProviderException
     */
    public function exchangeAuthorizationCode(string $authorizationCode, string $redirectUri): GoogleAuthorization;
}
