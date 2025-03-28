<?php

namespace App\Service\Integrations\Google;

use App\Entity\Larp;
use App\Entity\LarpIntegration;
use App\Enum\IntegrationFileType;
use App\Enum\LarpIntegrationProvider;
use App\Repository\LarpIntegrationRepository;
use App\Repository\LarpRepository;
use App\Service\Integrations\Exceptions\ReAuthenticationNeededException;
use App\Service\Integrations\IntegrationServiceInterface;
use Google\Service\Drive;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;


readonly class GoogleIntegrationService implements IntegrationServiceInterface
{
    public const GOOGLE_SCOPES = [
        Drive::DRIVE,
        'email'
    ];

    public function __construct(
        private GoogleClientManager   $googleClientManager,
        private CacheInterface        $cache,
        private UrlGeneratorInterface $urlGenerator,
        private ClientRegistry        $clientRegistry,
        private LarpRepository            $larpRepository,
        private LarpIntegrationRepository $larpIntegrationRepository,
    )
    {
    }

    public function supports(LarpIntegrationProvider $provider): bool
    {
        return $provider === LarpIntegrationProvider::Google;
    }

    /** @see GoogleAuthenticator */
    public function connect(Larp $larp): Response
    {

        /** @var GoogleClient $client */
        $client = $this->clientRegistry->getClient(LarpIntegrationProvider::Google->value);

        /** @see https://developers.google.com/workspace/drive/picker/guides/overview */
        return $client->redirect(
            self::GOOGLE_SCOPES,
            [
                'access_type' => 'offline',
                'prompt' => 'consent',
                'redirect_uri' => $this->urlGenerator->generate('backoffice_larp_connect_integration_check', [
                    'provider' => LarpIntegrationProvider::Google->value,
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
    }

    public function finalizeConnection(string $larpId, AccessTokenInterface $token, ResourceOwnerInterface $user): void
    {
        $this->createGoogleDriveIntegration($token, $user, $larpId);

    }

    /**
     * @throws ReAuthenticationNeededException
     */
    public function getClient(LarpIntegration $integration): object
    {
        return $this->googleClientManager->getClientForIntegration($integration);
    }

    public function getOwnerNameFromOwner(ResourceOwnerInterface $owner): ?string
    {
        return match (true) {
            $owner instanceof GoogleUser => $owner->getEmail(),
            default => null,
        };
    }

    public function createGoogleDriveIntegration(
        AccessToken $accessToken,
        ResourceOwnerInterface $owner,
        string $larpId
    ): LarpIntegration
    {
        $larp = $this->larpRepository->find($larpId);
        if (!$larp) {
            throw new \Exception("Larp not found.");
        }
        $integrationOwnerName = $this->getOwnerNameFromOwner($owner);

        $tokenValues = $accessToken->getValues();
        $grantedScopes = $tokenValues['scope'] ?? null;
        $integration = new LarpIntegration();
        $integration->setProvider(LarpIntegrationProvider::Google);
        $integration->setAccessToken($accessToken->getToken());
        $integration->setRefreshToken($accessToken->getRefreshToken());
        $integration->setExpiresAt((new \DateTime())->setTimestamp($accessToken->getExpires()));
        $integration->setScopes($grantedScopes);
        $integration->setLarp($larp);
        $integration->setOwner($integrationOwnerName);

        $this->larpIntegrationRepository->save($integration);

        return $integration;
    }

    public function listSpreadsheets(LarpIntegration $integration): array
    {
        $client = $this->googleClientManager->getClientForIntegration($integration);
        $driveService = new Drive($client);

        // Query to list only spreadsheets.
        $query = "mimeType='application/vnd.google-apps.spreadsheet'";
        $params = [
            'q' => $query,
            'fields' => 'files(id, name)',
        ];

        $files = $driveService->files->listFiles($params);

        $spreadsheets = [];
        foreach ($files->getFiles() as $file) {
            $spreadsheets[] = [
                'id' => $file->getId(),
                'name' => $file->getName(),
            ];
        }

        return $spreadsheets;
    }

    public function getFolderContents(LarpIntegration $integration, string $folderId = 'root', bool $refresh = false): array
    {
        $cacheKey = 'google_drive_folder_integration' . $integration->getId() . '_folder_' . $folderId;

        if (!$refresh) {
            $cachedData = $this->cache->get($cacheKey, function (ItemInterface $item) {
                $item->expiresAfter(3600 * 24);
                return null;
            });

            if ($cachedData !== null) {
                return $cachedData;
            }
        }

        $client = $this->googleClientManager->getClientForIntegration($integration);
        $driveService = new Drive($client);

        $items = [];
        $pageToken = null;

        if ($folderId === 'root') {
            $query = "trashed = false and ('root' in parents or (mimeType = 'application/vnd.google-apps.folder' and sharedWithMe))";

            $query = "trashed = false and (
        (mimeType = 'application/vnd.google-apps.folder' and sharedWithMe) 
        or ('root' in parents) 
       
    )";
            // or (sharedWithMe and not 'root' in parents and parents is null)
        } else {
            $query = "trashed = false and ('$folderId' in parents)";
        }

        do {
            $params = [
                'q' => $query,
                'fields' => 'nextPageToken, files(id, name, mimeType, owners(displayName, emailAddress))',
                'pageSize' => 100,
                'pageToken' => $pageToken,
            ];
            $response = $driveService->files->listFiles($params);

            foreach ($response->getFiles() as $file) {
                $items[$file->getId()] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'type' => IntegrationFileType::fromMimeType($file->getMimeType())->value,
                    'owner' => $file->getOwners()[0]->displayName ?? 'Unknown',
                    'children' => [], // Placeholder for subfolders
                ];
            }

            $pageToken = $response->getNextPageToken();
        } while ($pageToken !== null);

        $this->cache->get($cacheKey, function (ItemInterface $item) use ($items) {
            $item->expiresAfter(3600);
            return $items;
        });

        return $items;
    }

}
