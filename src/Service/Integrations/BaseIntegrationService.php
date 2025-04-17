<?php

namespace App\Service\Integrations;

use App\Entity\Enum\TargetType;
use App\Entity\LarpIntegration;
use App\Entity\ObjectFieldMapping;
use App\Entity\StoryObject;

abstract readonly class BaseIntegrationService
{

    public function syncStoryObject(LarpIntegration $integration, StoryObject $storyObject): void
    {
        //all mappings configured for this LarpIntegration for this type of story object
        $mappings = $this->findMatchingFileMappingsForStoryObject($integration, $storyObject::getTargetType());
        foreach ($mappings as $mapping) {
            if ($mapping->getFileType()->isList()) {
                $this->syncStoryObjectList($mapping, $storyObject);
            } elseif ($mapping->getFileType()->isDocument()) {
                $this->syncStoryObjectDocument($mapping, $storyObject);
            }
        }
    }

    /**
     * @return ObjectFieldMapping[]
     */
    protected function findMatchingFileMappingsForStoryObject(LarpIntegration $integration, TargetType $targetType): array
    {
        $matching = [];

        foreach ($integration->getSharedFiles() as $sharedFile) {
            foreach ($sharedFile->getMappings() as $mapping) {
                if ($mapping->getFileType()->matchesTargetType($targetType)) {
                    $matching[] = $mapping;
                }
            }
        }

        return $matching;
    }

    protected abstract function syncStoryObjectList(ObjectFieldMapping $mapping, StoryObject $storyObject);
    protected abstract function syncStoryObjectDocument(ObjectFieldMapping $mapping, StoryObject $storyObject);

}
