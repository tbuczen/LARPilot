<?php

namespace App\Service\Integrations\Google;

use App\Entity\LarpIntegration;
use App\Enum\IntegrationFileType;
use App\Enum\LarpIntegrationProvider;
use App\Service\Integrations\IntegrationServiceInterface;
use Google\Service\Drive;
use Google\Service\Sheets;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;


readonly class GoogleIntegrationService implements IntegrationServiceInterface
{


    public function __construct(
        private GoogleClientManager $googleClientManager,
        private CacheInterface $cache,
    ) {
    }

    public function supports(LarpIntegrationProvider $provider): bool
    {
        return $provider === LarpIntegrationProvider::Google;
    }

    public function listSpreadsheets(LarpIntegration $integration): array
    {
        $client = $this->googleClientManager->getClientForIntegration($integration);
        $driveService = new Drive($client);

        // Query to list only spreadsheets.
        $query = "mimeType='application/vnd.google-apps.spreadsheet'";
        $params = [
            'q'      => $query,
            'fields' => 'files(id, name)',
        ];

        $files = $driveService->files->listFiles($params);

        $spreadsheets = [];
        foreach ($files->getFiles() as $file) {
            $spreadsheets[] = [
                'id'   => $file->getId(),
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

        if($folderId === 'root'){
            $query = "trashed = false and ('root' in parents or (mimeType = 'application/vnd.google-apps.folder' and sharedWithMe))";

            $query = "trashed = false and (
        (mimeType = 'application/vnd.google-apps.folder' and sharedWithMe) 
        or ('root' in parents) 
       
    )";
            // or (sharedWithMe and not 'root' in parents and parents is null)
        }else{
            $query = "trashed = false and ('$folderId' in parents)";
        }

        do {
            $params = [
                'q'      => $query,
                'fields' => 'nextPageToken, files(id, name, mimeType, owners(displayName, emailAddress))',
                'pageSize' => 100,
                'pageToken' => $pageToken,
            ];
            $response = $driveService->files->listFiles($params);

            foreach ($response->getFiles() as $file) {
                $items[$file->getId()] = [
                    'id'       => $file->getId(),
                    'name'     => $file->getName(),
                    'type' => IntegrationFileType::fromMimeType($file->getMimeType())->value,
                    'owner'    => $file->getOwners()[0]->displayName ?? 'Unknown',
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
