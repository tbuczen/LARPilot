<?php

namespace App\Domain\Integrations\Service\Sharing;

use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Google\GoogleClientManager;
use Google\Service\Drive;
use Google\Service\Drive\Permission;

final readonly class GoogleDriveSharingService implements DriveSharingServiceInterface
{
    public function __construct(
        private GoogleClientManager $clientManager,
    ) {
    }

    public function ensureShared(
        LarpIntegration $integration,
        string $fileId,
        string $fileName,
        string $permissionType
    ): void {
        $client = $this->clientManager->getClientForIntegration($integration);
        $drive = new Drive($client);
        $serviceAccountEmail = $this->clientManager->getServiceAccountEmail();

        $existingPermissions = $drive->permissions->listPermissions($fileId, [
            'fields' => 'permissions(id, role, type, emailAddress)',
        ]);

        foreach ($existingPermissions->getPermissions() as $perm) {
            if ($perm->getEmailAddress() === $serviceAccountEmail && $perm->getRole() === $permissionType) {
                return;
            }
        }

        $newPermission = new Permission();
        $newPermission->setType('user');
        $newPermission->setRole($permissionType);
        $newPermission->setEmailAddress($serviceAccountEmail);

        $drive->permissions->create($fileId, $newPermission);
    }
}
