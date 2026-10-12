<?php

namespace App\Domain\Integrations\Service\Google;

use League\OAuth2\Client\Token\AccessTokenInterface;

final readonly class GoogleAuthorization
{
    public function __construct(
        public AccessTokenInterface $accessToken,
        public ?string $accountEmail,
    ) {
    }
}
