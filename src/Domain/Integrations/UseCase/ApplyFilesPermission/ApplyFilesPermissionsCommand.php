<?php

namespace App\Domain\Integrations\UseCase\ApplyFilesPermission;

readonly class ApplyFilesPermissionsCommand
{

    public function __construct(
        public string $integrationId,
        public array  $permissions // File ID => Permission type (edit/view)
    ) {}
}