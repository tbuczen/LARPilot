<?php

namespace App\Service\Integrations;

use App\Entity\Enum\LarpIntegrationProvider;
use App\Entity\Enum\ReferenceType;
use App\Entity\Larp;
use App\Entity\LarpIntegration;
use App\Entity\ObjectFieldMapping;
use App\Entity\SharedFile;
use App\Entity\StoryObject\StoryObject;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\HttpFoundation\Response;

interface IntegrationServiceInterface
{
    public function supports(LarpIntegrationProvider $provider): bool;

    public function getClient(LarpIntegration $integration): object;

    public function connect(Larp $larp): Response;

    public function finalizeConnection(string $larpId, AccessTokenInterface $token, ResourceOwnerInterface $user): void;

    public function getOwnerNameFromOwner(ResourceOwnerInterface $owner): ?string;

    public function getExternalFileUrl(LarpIntegration $integration, string $externalFileId);

    /**
     * @param SharedFile $sharedFile
     * @return array
     * @throws \Exception - some services might not implement it
     */
    public function fetchSpreadsheetRows(SharedFile $sharedFile, ObjectFieldMapping $mapping): array;

    public function createReferenceUrl(
        SharedFile $file,
        ReferenceType $referenceType,
        string|int $externalId,
        array $additionalData = []
    ): ?string;

    public function syncStoryObject(LarpIntegration $integration, StoryObject $storyObject);
    public function removeStoryObject(LarpIntegration $integration, StoryObject $storyObject);
    public function createStoryObject(LarpIntegration $integration, StoryObject $storyObject);

    public function fetchSpreadsheetSheetIdByName(SharedFile $sharedFile, ObjectFieldMapping $mapping): string;
}
