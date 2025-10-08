<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

/** @see https://stackoverflow.com/questions/75890476/invalid-scopes-email-openid-public-profile */
class OAuthFacebookController extends AbstractController
{
    #[Route('/connect/facebook', name: 'connect_facebook_start')]
    public function connectFacebook(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('facebook')
            ->redirect(['public_profile', 'email'], []);
    }

    #[Route('/connect/facebook/check', name: 'connect_facebook_check')]
    public function connectFacebookCheck(): void
    {
        // This route is used by the OAuth2 client bundle to handle the callback.
        // You can leave it empty; the bundle will intercept the request and handle authentication.
    }
}
