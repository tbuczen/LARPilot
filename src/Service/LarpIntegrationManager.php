<?php

namespace App\Service;

use App\Entity\LarpIntegration;
use App\Enum\LarpIntegrationProvider;
use App\Repository\LarpIntegrationRepository;
use App\Repository\LarpRepository;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;

final readonly class LarpIntegrationManager
{

    const GOOGLE_SCOPES = ['https://www.googleapis.com/auth/drive.file'];
    public function __construct(
        private ClientRegistry            $clientRegistry,
        private LarpRepository            $larpRepository,
        private LarpIntegrationRepository $larpIntegrationRepository,
    ) {
    }

    public function createGoogleDriveIntegration(object $accessToken, string $larpId): void
    {
        $larp = $this->larpRepository->find($larpId);
        if (!$larp) {
            throw new \Exception("Larp not found.");
        }

        $tokenValues = $accessToken->getValues();
        $grantedScopes = $tokenValues['scope'] ?? null;

        $integration = new LarpIntegration();
        $integration->setProvider(LarpIntegrationProvider::Google);
        $integration->setAccessToken($accessToken->getToken());
        $integration->setRefreshToken($accessToken->getRefreshToken());
        $integration->setExpiresAt((new \DateTime())->setTimestamp($accessToken->getExpires()));
        $integration->setScopes($grantedScopes ?? implode(' ', self::GOOGLE_SCOPES));
        $integration->setLarp($larp);

        $this->larpIntegrationRepository->create($integration);
    }

    public function decorateIntegrationsWithClient(array $integrations): void
    {
        /** @var LarpIntegration $integration */
        foreach ($integrations as $integration) {
            $oauthClient = $this->clientRegistry->getClient($integration->getProvider()->value);
            $integration->setClient($oauthClient);
        }
    }
}
