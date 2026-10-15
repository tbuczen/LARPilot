<?php

namespace App\Domain\Integrations\UseCase\ApplyFilesPermission;

readonly class ApplyFilesPermissionsCommand
{
    /**
     * @param list<array{fileId: string, fileName: string, permission: string, mimeType: string}> $selectedFiles
     */
    public function __construct(
        public string $integrationId,
        public array  $selectedFiles,
    ) {
    }
}
