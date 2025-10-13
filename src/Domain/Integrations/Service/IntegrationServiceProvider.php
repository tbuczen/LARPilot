<?php

namespace App\Domain\Integrations\Service;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;

final readonly class IntegrationServiceProvider
{
    /**
     * @param iterable<IntegrationServiceInterface> $integrationServices
     */
    public function __construct(
        private iterable $integrationServices
    ) {
    }

    public function getServiceForIntegration(LarpIntegrationProvider $provider): IntegrationServiceInterface
    {
        foreach ($this->integrationServices as $integrationService) {
            if ($integrationService->supports($provider)) {
                return $integrationService;
            }
        }

        throw new \LogicException(sprintf("There is no service implementing IntegrationServiceInterface that supports %s", $provider->value));
    }
}
