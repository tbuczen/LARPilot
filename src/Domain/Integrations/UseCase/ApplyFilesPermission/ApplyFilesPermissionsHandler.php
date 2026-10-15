<?php

namespace App\Domain\Integrations\UseCase\ApplyFilesPermission;

use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Repository\SharedFileRepository;
use App\Domain\Integrations\Service\Capability\FileSourceIntegrationInterface;
use App\Domain\Integrations\Service\IntegrationRegistry;
use App\Domain\Integrations\Service\Sharing\DriveSharingServiceInterface;
use App\Domain\Integrations\Service\Sharing\DriveSharingServiceProvider;
use Doctrine\ORM\EntityManagerInterface;

readonly class ApplyFilesPermissionsHandler
{
    public function __construct(
        private IntegrationRegistry          $integrationRegistry,
        private LarpIntegrationRepository   $larpIntegrationRepository,
        private SharedFileRepository        $sharedFileRepository,
        private DriveSharingServiceProvider $sharingServiceProvider,
        private EntityManagerInterface      $entityManager,
    ) {
    }

    public function handle(ApplyFilesPermissionsCommand $command): void
    {
        $integration = $this->larpIntegrationRepository->find($command->integrationId);
        if (!$integration instanceof LarpIntegration) {
            throw new \InvalidArgumentException(sprintf('Integration "%s" not found.', $command->integrationId));
        }

        $sharingService = $this->sharingServiceProvider->getServiceFor($integration);
        $selectedFilesById = [];
        foreach ($command->selectedFiles as $selectedFile) {
            $selectedFilesById[$selectedFile['fileId']] = $selectedFile;
        }

        $this->entityManager->wrapInTransaction(function () use ($integration, $sharingService, $selectedFilesById): void {
            $sharedFilesById = [];
            foreach ($integration->getSharedFiles()->toArray() as $sharedFile) {
                if (!array_key_exists($sharedFile->getFileId(), $selectedFilesById)) {
                    $this->removeSharedFile($integration, $sharedFile);
                    continue;
                }
                $sharedFilesById[$sharedFile->getFileId()] = $sharedFile;
            }

            foreach ($selectedFilesById as $fileId => $selectedFile) {
                $this->applySelectedFile($integration, $sharingService, $selectedFile, $sharedFilesById[$fileId] ?? null);
            }

            $this->sharedFileRepository->flush();
        });
    }

    /**
     * @param array{fileId: string, fileName: string, permission: string, mimeType: string} $selectedFile
     */
    private function applySelectedFile(
        LarpIntegration $integration,
        DriveSharingServiceInterface $sharingService,
        array $selectedFile,
        ?SharedFile $sharedFile,
    ): void {
        $sharingService->ensureShared(
            $integration,
            $selectedFile['fileId'],
            $selectedFile['fileName'],
            $selectedFile['permission']
        );

        if (!$sharedFile instanceof SharedFile) {
            $this->createSharedFile($integration, $selectedFile);
            return;
        }

        $sharedFile->setPermissionType($selectedFile['permission']);
        $sharedFile->setFileName($selectedFile['fileName']);
    }

    private function removeSharedFile(LarpIntegration $integration, SharedFile $sharedFile): void
    {
        foreach ($sharedFile->getMappings() as $mapping) {
            $this->entityManager->remove($mapping);
        }

        $integration->removeSharedFile($sharedFile);
        $this->entityManager->remove($sharedFile);
    }

    /**
     * @param array{fileId: string, fileName: string, permission: string, mimeType: string} $file
     */
    private function createSharedFile(LarpIntegration $integration, array $file): void
    {
        $integrationService = $this->integrationRegistry->capability($integration, FileSourceIntegrationInterface::class);
        $url = $integrationService->getExternalFileUrl($integration, $file['fileId']);

        $sharedFile = new SharedFile();
        $sharedFile->setIntegration($integration);
        $sharedFile->setFileId($file['fileId']);
        $sharedFile->setFileName($file['fileName']);
        $sharedFile->setMimeType($file['mimeType']);
        $sharedFile->setPermissionType($file['permission']);
        $sharedFile->setUrl($url);
        $sharedFile->setMetadata([]);
        $integration->addSharedFile($sharedFile);

        $this->sharedFileRepository->save($sharedFile, false);
    }
}
