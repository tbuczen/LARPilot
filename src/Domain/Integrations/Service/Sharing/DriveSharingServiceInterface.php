<?php

namespace App\Domain\Integrations\Service\Sharing;

use App\Domain\Integrations\Entity\LarpIntegration;

interface DriveSharingServiceInterface
{
    public function ensureShared(
        LarpIntegration $integration,
        string $fileId,
        string $fileName,
        string $permissionType // e.g. 'reader' or 'writer'
    ): void;
}
