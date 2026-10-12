<?php

namespace App\Domain\Integrations\Service\Capability;

use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;

interface AccessTokenProviderInterface extends CredentialProviderInterface
{
    /**
     * @throws ReAuthenticationNeededException
     */
    public function accessToken(LarpIntegration $integration): string;
}
