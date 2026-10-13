<?php

namespace App\Domain\Integrations\Service\Hub;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;

final readonly class IntegrationCard
{
    public function __construct(
        public LarpIntegrationProvider $provider,
        public IntegrationHubState $state,
        public ?LarpIntegration $integration,
        public bool $connectable,
        public ?string $accountLabel,
        public ?\DateTimeInterface $lastSyncedAt,
        public int $sourceCount,
    ) {
    }

    public function purposeKey(): string
    {
        return 'integration_hub.purpose.' . $this->provider->value;
    }

    public function connectedIntegration(): LarpIntegration
    {
        if (!$this->integration instanceof LarpIntegration) {
            throw new \LogicException(sprintf('%s is not connected.', $this->provider->getLabel()));
        }

        return $this->integration;
    }
}
