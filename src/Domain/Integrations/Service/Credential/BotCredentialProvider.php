<?php

namespace App\Domain\Integrations\Service\Credential;

use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Capability\CredentialProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class BotCredentialProvider implements CredentialProviderInterface
{
    public function __construct(
        #[Autowire('%env(DISCORD_BOT_TOKEN)%')]
        private string $discordBotToken,
    ) {
    }

    public function authorizationHeader(LarpIntegration $integration): string
    {
        return 'Bot ' . $this->discordBotToken;
    }
}
