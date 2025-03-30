<?php

namespace App\Form\Models;

use App\Entity\ObjectFieldMapping;
use App\Enum\FileMappingType;

final class SpreadsheetMappingModel
{

    public function __construct(
        public ?FileMappingType $mappingType = null,
        public ?int             $startingRow = null,
        public ?string          $factionColumn = null,
        public ?string          $characterNameColumn = null,
        public ?string          $inGameNameColumn = null
    )
    {
    }

    public static function fromEntity(?ObjectFieldMapping $mapping): ?self
    {
        if ($mapping === null) {
            return null;
        }

        $mappingConfiguration = $mapping->getMappingConfiguration();
        return new self(
            $mapping->getFileType(),
            $mappingConfiguration['startingRow'] ?? null,
            $mappingConfiguration['factionColumn'] ?? null,
            $mappingConfiguration['characterNameColumn'] ?? null,
            $mappingConfiguration['inGameNameColumn'] ?? null
        );
    }

}
