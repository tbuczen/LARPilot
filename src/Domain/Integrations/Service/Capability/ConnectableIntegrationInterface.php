<?php

namespace App\Domain\Integrations\Service\Capability;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\DTO\ConnectionCallback;
use App\Domain\Integrations\DTO\IntegrationHealth;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Exceptions\IntegrationConnectionFailedException;

interface ConnectableIntegrationInterface extends IntegrationProviderInterface
{
    public function authorizationUrl(Larp $larp, string $state, string $redirectUri): string;

    /**
     * @throws IntegrationConnectionFailedException
     */
    public function completeConnection(Larp $larp, ConnectionCallback $callback, User $connectingUser): LarpIntegration;

    public function disconnect(LarpIntegration $integration): void;

    public function health(LarpIntegration $integration): IntegrationHealth;
}
