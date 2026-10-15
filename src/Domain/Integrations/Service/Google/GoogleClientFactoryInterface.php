<?php

namespace App\Domain\Integrations\Service\Google;

use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use Google\Client;

interface GoogleClientFactoryInterface
{
    public function createServiceAccountClient(): Client;

    public function getServiceAccountEmail(): string;

    /**
     * @throws ReAuthenticationNeededException
     */
    public function getClientForIntegration(LarpIntegration $integration): Client;
}
