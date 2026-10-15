<?php

namespace App\Domain\Integrations\Service\Capability;

use App\Domain\Integrations\DTO\RemoteItem;
use App\Domain\Integrations\DTO\RemoteSource;
use App\Domain\Integrations\Entity\LarpIntegration;

interface ConversationSourceIntegrationInterface extends IntegrationProviderInterface
{
    /**
     * @return list<RemoteSource>
     */
    public function listChannels(LarpIntegration $integration): array;

    /**
     * @return list<RemoteItem>
     */
    public function listThreads(LarpIntegration $integration, string $channelId): array;
}
