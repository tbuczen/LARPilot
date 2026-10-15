<?php

namespace App\Domain\Integrations\Miro\OAuth;

use App\Domain\Integrations\Service\Exceptions\IntegrationConnectionFailedException;

interface MiroOAuthGatewayInterface
{
    public function authorizationUrl(string $state, string $redirectUri): string;

    /**
     * @throws IntegrationConnectionFailedException
     */
    public function exchangeAuthorizationCode(string $authorizationCode, string $redirectUri): MiroAuthorization;
}
