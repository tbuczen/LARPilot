<?php

namespace App\Service\Integrations;

use App\Service\Integrations\Exceptions\ReAuthenticationNeededException;

interface OAuthTokenProviderInterface
{
    /**
     * @param string $integrationId
     * @return string|null
     * @throws ReAuthenticationNeededException
     */
    public function getTokenForIntegration(string $integrationId): ?string;
}
