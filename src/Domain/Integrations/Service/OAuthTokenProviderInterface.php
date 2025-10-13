<?php

namespace App\Domain\Integrations\Service;

use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;

interface OAuthTokenProviderInterface
{
    /**
     * @param string $integrationId
     * @return string|null
     * @throws ReAuthenticationNeededException
     */
    public function getTokenForIntegration(string $integrationId): ?string;
}
