<?php

namespace App\Service\Integrations;

use App\Entity\Larp;
use App\Entity\LarpIntegration;
use App\Entity\SharedFile;
use App\Enum\LarpIntegrationProvider;
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
    public function fetchSpreadsheetRows(SharedFile $sharedFile): array;

}