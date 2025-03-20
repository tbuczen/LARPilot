<?php

namespace App\Service\Integrations;

use App\Enum\LarpIntegrationProvider;

interface IntegrationServiceInterface
{

    public function supports(LarpIntegrationProvider $provider): bool;


}