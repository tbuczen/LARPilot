<?php

namespace App\Service\Integrations\Sharing;

use App\Entity\LarpIntegration;

interface DriveSharingServiceInterface
{
    public function ensureShared(
        LarpIntegration $integration,
        string $fileId,
        string $fileName,
        string $permissionType // e.g. 'reader' or 'writer'
    ): void;
}
