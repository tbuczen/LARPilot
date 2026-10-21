<?php

namespace App\Domain\Integrations\Twig;

use App\Domain\Integrations\Discord\DiscordBotPermissions;
use App\Domain\Integrations\Entity\LarpIntegration;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class DiscordBotPermissionsExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('discord_bot_can_post', static fn (LarpIntegration $integration): bool => DiscordBotPermissions::canPost($integration)),
        ];
    }
}
