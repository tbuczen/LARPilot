<?php

namespace App\Domain\Integrations\Service;

use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\StoryObject;

abstract readonly class BaseIntegrationService
{
    public function createStoryObject(LarpIntegration $integration, StoryObject $storyObject): void
    {
        //all mappings configured for this LarpIntegration for this type of story object
        $mappings = $this->findMatchingFileMappingsForStoryObject($integration, $storyObject::getTargetType());

        foreach ($mappings as $mapping) {
            if ($mapping->getFileType()->isSpreadsheet()) {
                $this->createStoryObjectList($mapping, $storyObject);
            } elseif ($mapping->getFileType()->isDocument()) {
                $this->createStoryObjectDocument($mapping, $storyObject);
            }
        }
        //add
        //        ReferenceType
    }

    public function removeStoryObject(LarpIntegration $integration, StoryObject $storyObject): void
    {
        //      TODO:: get  $externalReferences, iterate over and do magic
        throw new \RuntimeException('Not implemented yet');
    }

    public function syncStoryObject(LarpIntegration $integration, StoryObject $storyObject): void
    {
        //In update iterate over external references saved for this integration
        throw new \RuntimeException('Not implemented yet');
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

    abstract protected function createStoryObjectList(ObjectFieldMapping $mapping, StoryObject $storyObject);
    abstract protected function createStoryObjectDocument(ObjectFieldMapping $mapping, StoryObject $storyObject);
}
