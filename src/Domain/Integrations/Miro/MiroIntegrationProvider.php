<?php

namespace App\Domain\Integrations\Miro;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Capability\AccessTokenProviderInterface;
use App\Domain\Integrations\Service\Capability\IntegrationProviderInterface;
use App\Domain\Integrations\Service\Credential\OAuthCredentialProvider;

readonly class MiroIntegrationProvider implements IntegrationProviderInterface, AccessTokenProviderInterface
{
    public function __construct(
        private OAuthCredentialProvider $oauthCredentialProvider,
    ) {
    }

    public static function providerKey(): string
    {
        return LarpIntegrationProvider::Miro->value;
    }

    public function authorizationHeader(LarpIntegration $integration): string
    {
        return $this->oauthCredentialProvider->authorizationHeader($integration);
    }

    public function accessToken(LarpIntegration $integration): string
    {
        return $this->oauthCredentialProvider->accessToken($integration);
    }
}
