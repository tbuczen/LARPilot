<?php

namespace App\Form\Models;

use App\Entity\ObjectFieldMapping;
use App\Enum\FileMappingType;

final class SpreadsheetMappingModel
{

    public function __construct(
        public ?FileMappingType $mappingType = FileMappingType::CHARACTER_LIST,
        public ?int             $startingRow = 2,
        public ?string             $sheetName = null,
        public ?string             $endColumn = null,
        /** @var array<string, string> */
        public array $columnMappings = []
    )
    {
    }

    public static function fromEntity(?ObjectFieldMapping $mapping): self
    {
        if ($mapping === null) {
            return new self();
        }

        $mappingConfiguration = $mapping->getMappingConfiguration();
        return new self(
            $mapping->getFileType(),
            $mappingConfiguration['startingRow'] ?? null,
            $mappingConfiguration['sheetName'] ?? null,
            $mappingConfiguration['endColumn'] ?? null,
            $mappingConfiguration['columnMappings'] ?? null
        );
    }

}
