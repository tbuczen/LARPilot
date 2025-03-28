<?php

namespace App\Service\Integrations\Sharing;

use App\Enum\LarpIntegrationProvider;
use App\Entity\LarpIntegration;

final readonly class DriveSharingServiceProvider
{
    /**
     * @param array<LarpIntegrationProvider::value, DriveSharingServiceInterface> $services
     */
    public function __construct(
        private iterable $services,
    ) {}

    public function getServiceFor(LarpIntegration $integration): DriveSharingServiceInterface
    {
        $key = $integration->getProvider()->value;

        if (!isset($this->services[$key])) {
            throw new \RuntimeException("No sharing service registered for provider: $key");
        }

        return $this->services[$key];
    }
}
