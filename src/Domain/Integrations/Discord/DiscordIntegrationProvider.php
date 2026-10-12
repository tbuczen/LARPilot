<?php

namespace App\Domain\Integrations\Discord;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Capability\CredentialProviderInterface;
use App\Domain\Integrations\Service\Capability\IntegrationProviderInterface;
use App\Domain\Integrations\Service\Credential\BotCredentialProvider;

readonly class DiscordIntegrationProvider implements IntegrationProviderInterface, CredentialProviderInterface
{
    public function __construct(
        private BotCredentialProvider $botCredentialProvider,
    ) {
    }

    public static function providerKey(): string
    {
        return LarpIntegrationProvider::Discord->value;
    }

    public function authorizationHeader(LarpIntegration $integration): string
    {
        return $this->botCredentialProvider->authorizationHeader($integration);
    }
}
