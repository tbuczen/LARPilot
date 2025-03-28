<?php


namespace App\Domain\Integrations\UseCase\ApplyFilesPermission;

use App\Entity\LarpIntegration;
use App\Entity\SharedFile;
use App\Repository\LarpIntegrationRepository;
use App\Repository\SharedFileRepository;
use App\Service\Integrations\Sharing\DriveSharingServiceProvider;
use Doctrine\ORM\EntityManagerInterface;

readonly class ApplyFilesPermissionsHandler
{
    public function __construct(
        private LarpIntegrationRepository    $larpIntegrationRepository,
        private SharedFileRepository         $sharedFileRepository,
        private DriveSharingServiceProvider $sharingServiceProvider,
        private EntityManagerInterface       $entityManager,
    )
    {
    }

    public function handle(ApplyFilesPermissionsCommand $command): void
    {
        $integration = $this->larpIntegrationRepository->find($command->integrationId);
        $sharingService = $this->sharingServiceProvider->getServiceFor($integration);

        $this->entityManager->wrapInTransaction(function () use ($command, $integration, $sharingService): void {
            foreach ($command->permissions as $file) {
                try {
                    $sharingService->ensureShared(
                        $integration,
                        $file['fileId'],
                        $file['fileName'],
                        $file['permission']
                    );

                    if (!$this->sharedFileRepository->existsForIntegration($integration, $file['fileId'])) {
                        $this->createSharedFile($integration, $file);
                    }

                } catch (\Throwable $e) {
                    // log or notify admin
                    throw $e;
                }
            }

            $this->sharedFileRepository->flush();
        });
    }

    function createSharedFile(LarpIntegration $integration, array $file): void
    {
        $sharedFile = new SharedFile();
        $sharedFile->setIntegration($integration);
        $sharedFile->setFileId($file['fileId']);
        $sharedFile->setFileName($file['fileName']);
        $sharedFile->setMimeType($file['mimeType']);
        $sharedFile->setPermissionType($file['permission']);
        $sharedFile->setMetadata([]);

        $this->sharedFileRepository->save($sharedFile, false);
    }
}
