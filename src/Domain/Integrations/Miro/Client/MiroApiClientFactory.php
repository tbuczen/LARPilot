<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MiroApiClientFactory
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function create(string $accessToken): MiroApiClient
    {
        return new MiroApiClient($this->httpClient, $accessToken);
    }
}
