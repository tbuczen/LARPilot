<?php

namespace App\Domain\Integrations\Service\Credential;

use App\Domain\Integrations\DTO\RefreshedToken;
use App\Domain\Integrations\Service\Exceptions\InvalidGrantException;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface OAuthTokenRefresherInterface
{
    public static function providerKey(): string;

    /**
     * @throws InvalidGrantException
     */
    public function refresh(string $refreshToken): RefreshedToken;
}
