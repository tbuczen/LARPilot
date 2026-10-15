<?php

namespace App\Domain\Integrations\Service\Capability;

use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;

interface CredentialProviderInterface
{
    /**
     * @throws ReAuthenticationNeededException
     */
    public function authorizationHeader(LarpIntegration $integration): string;
}
