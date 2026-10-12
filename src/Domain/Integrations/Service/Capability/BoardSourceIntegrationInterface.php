<?php

namespace App\Domain\Integrations\Service\Capability;

use App\Domain\Integrations\DTO\RemoteItem;
use App\Domain\Integrations\DTO\RemoteSource;
use App\Domain\Integrations\Entity\LarpIntegration;

interface BoardSourceIntegrationInterface extends IntegrationProviderInterface
{
    /**
     * @return list<RemoteSource>
     */
    public function listBoards(LarpIntegration $integration): array;

    /**
     * @return list<RemoteItem>
     */
    public function listBoardItems(LarpIntegration $integration, string $boardId): array;
}
