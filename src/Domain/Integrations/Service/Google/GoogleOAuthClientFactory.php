<?php

namespace App\Domain\Integrations\Service\Google;

use Google\Client;

class GoogleOAuthClientFactory
{
    public function __construct(
        private readonly string $googleClientId,
        private readonly string $googleClientSecret,
    ) {
    }

    public function create(): Client
    {
        return new Client([
            'client_id' => $this->googleClientId,
            'client_secret' => $this->googleClientSecret,
        ]);
    }
}
