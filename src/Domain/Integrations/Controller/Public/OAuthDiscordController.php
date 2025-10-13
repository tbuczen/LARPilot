<?php

namespace App\Domain\Integrations\Controller\Public;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class OAuthDiscordController extends AbstractController
{
    #[Route('/connect/discord', name: 'connect_discord_start')]
    public function connectDiscord(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('discord')
            ->redirect(['identify', 'email'], []);
    }

    #[Route('/connect/discord/check', name: 'connect_discord_check')]
    public function connectDiscordCheck(): void
    {
        // This route is used by the OAuth2 client bundle to handle the callback.
        // You can leave it empty; the bundle will intercept the request and handle authentication.
    }
}
