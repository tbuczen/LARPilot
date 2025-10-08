<?php

namespace App\Form\Models;

use App\Entity\Enum\ResourceType;
use App\Entity\ObjectFieldMapping;

class ExternalResourceMappingModel
{
    public function __construct(
        public ?ResourceType $mappingType = ResourceType::CHARACTER_LIST,
        /** @var array<string, string> */
        public array         $mappings = [],
        public array         $meta = []
    ) {
    }

    public static function fromEntity(?ObjectFieldMapping $mapping): self
    {
        if ($mapping === null) {
            return new self();
        }

        return new self(
            $mapping->getFileType(),
            $mapping->getMappingConfiguration() ?? [],
            $mapping->getMetaConfiguration() ?? [],
        );
    }
}
