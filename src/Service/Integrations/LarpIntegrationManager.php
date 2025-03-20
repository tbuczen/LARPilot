<?php

namespace App\Service\Integrations;

use App\Entity\LarpIntegration;
use App\Enum\LarpIntegrationProvider;
use App\Repository\LarpIntegrationRepository;
use App\Repository\LarpRepository;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Token\AccessToken;

final readonly class LarpIntegrationManager
{

    const GOOGLE_SCOPES = [
//        'https://www.googleapis.com/auth/drive.file',
        'https://www.googleapis.com/auth/drive.metadata.readonly',
//        'https://www.googleapis.com/auth/drive.readonly',
//        'https://www.googleapis.com/auth/spreadsheets.readonly'
    ];
    public function __construct(
        private ClientRegistry            $clientRegistry,
        private LarpRepository            $larpRepository,
        private LarpIntegrationRepository $larpIntegrationRepository,
    ) {
    }

    public function createGoogleDriveIntegration(AccessToken $accessToken, string $larpId): LarpIntegration
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

        return $integration;
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
