<?php

namespace App\Form\Models;

use App\Entity\Enum\ResourceType;
use App\Entity\ObjectFieldMapping;

class SpreadsheetMappingModel extends ExternalResourceMappingModel
{
    public function __construct(
        public ?ResourceType $mappingType = ResourceType::CHARACTER_LIST,
        public ?int          $startingRow = 2,
        public ?string       $sheetName = null,
        public ?string       $endColumn = null,
        /** @var array<string, string> */
        public array         $mappings = []
    ) {
        parent::__construct($mappingType, $mappings);
    }

    public static function fromEntity(?ObjectFieldMapping $mapping): self
    {
        if ($mapping === null) {
            return new self();
        }

        $metaConfiguration = $mapping->getMetaConfiguration();
        $mappingConfiguration = $mapping->getMappingConfiguration();
        return new self(
            $mapping->getFileType(),
            $metaConfiguration['startingRow'] ?? null,
            $metaConfiguration['sheetName'] ?? null,
            $metaConfiguration['endColumn'] ?? null,
            $mappingConfiguration ?? null
        );
    }
}
