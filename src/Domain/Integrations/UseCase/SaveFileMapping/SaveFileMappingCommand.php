<?php

namespace App\Domain\Integrations\UseCase\SaveFileMapping;

final readonly class SaveFileMappingCommand
{
    public function __construct(
        public string $larpId,
        public string $provider,
        public string $mappingType,
        public string $sharedFileId,
        public array  $fields, // e.g. title, factionColumn, etc.
        public array  $meta, // e.g. startingRow, endColumn, etc.
    ) {
    }
}
