<?php

namespace App\Domain\Integrations\Form\Models;

use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\Integrations\Entity\ObjectFieldMapping;

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
        if (!$mapping instanceof ObjectFieldMapping) {
            return new self();
        }

        return new self(
            $mapping->getFileType(),
            $mapping->getMappingConfiguration() ?? [],
            $mapping->getMetaConfiguration() ?? [],
        );
    }
}
