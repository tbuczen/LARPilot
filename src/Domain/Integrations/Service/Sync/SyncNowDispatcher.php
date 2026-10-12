<?php

namespace App\Domain\Integrations\Service\Sync;

use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Message\SyncExternalSourceMessage;
use App\Domain\Integrations\Service\Capability\SourceSyncIntegrationInterface;
use App\Domain\Integrations\Service\IntegrationRegistry;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class SyncNowDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private IntegrationRegistry $integrationRegistry,
    ) {
    }

    public function canSync(ExternalSource $source): bool
    {
        $integration = $source->getIntegration();

        return $source->isEnabled()
            && $integration->getStatus()->isUsable()
            && $this->integrationRegistry->supports($integration->getProvider(), SourceSyncIntegrationInterface::class);
    }

    public function dispatch(ExternalSource $source): bool
    {
        if (!$this->canSync($source)) {
            return false;
        }

        $this->messageBus->dispatch(new SyncExternalSourceMessage($source->getId()));

        return true;
    }
}
