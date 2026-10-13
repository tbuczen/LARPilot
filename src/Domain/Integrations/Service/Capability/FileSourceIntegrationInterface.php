<?php

namespace App\Domain\Integrations\Service\Capability;

use App\Domain\Integrations\DTO\SpreadsheetPreview;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\StoryObject\Entity\StoryObject;

interface FileSourceIntegrationInterface extends IntegrationProviderInterface
{
    public function getExternalFileUrl(LarpIntegration $integration, string $externalFileId): string;

    /**
     * @return array<int, array<string, mixed>>
     * @throws \Exception
     */
    public function fetchSpreadsheetRows(SharedFile $sharedFile, ObjectFieldMapping $mapping): array;

    public function fetchSpreadsheetSheetIdByName(SharedFile $sharedFile, ObjectFieldMapping $mapping): string;

    /**
     * @throws \Exception
     */
    public function fetchSpreadsheetPreview(SharedFile $sharedFile, ?string $sheetName, int $headerRowNumber, int $rowLimit): SpreadsheetPreview;

    /**
     * @param array<string, mixed> $additionalData
     */
    public function createReferenceUrl(
        SharedFile $file,
        ReferenceType $referenceType,
        string|int $externalId,
        array $additionalData = []
    ): ?string;

    public function createStoryObject(LarpIntegration $integration, StoryObject $storyObject): void;

    public function syncStoryObject(LarpIntegration $integration, StoryObject $storyObject): void;

    public function removeStoryObject(LarpIntegration $integration, StoryObject $storyObject): void;
}
