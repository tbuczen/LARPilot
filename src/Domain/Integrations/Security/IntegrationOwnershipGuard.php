<?php

namespace App\Domain\Integrations\Security;

use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

readonly class IntegrationOwnershipGuard
{
    public function __construct(
        private LarpIntegrationRepository $larpIntegrationRepository,
    ) {
    }

    public function findIntegrationOfLarp(string $integrationId, Larp $larp): LarpIntegration
    {
        if (!Uuid::isValid($integrationId)) {
            throw new NotFoundHttpException();
        }

        $integration = $this->larpIntegrationRepository->find($integrationId);
        if (!$integration instanceof LarpIntegration) {
            throw new NotFoundHttpException();
        }

        $this->assertIntegrationBelongsToLarp($integration, $larp);

        return $integration;
    }

    public function assertIntegrationBelongsToLarp(LarpIntegration $integration, Larp $larp): void
    {
        if (!$integration->getLarp()->getId()->equals($larp->getId())) {
            throw new NotFoundHttpException();
        }
    }

    public function assertSharedFileBelongsToLarp(SharedFile $sharedFile, Larp $larp, ?LarpIntegrationProvider $provider = null): void
    {
        $integration = $sharedFile->getIntegration();
        $this->assertIntegrationBelongsToLarp($integration, $larp);

        if ($provider instanceof LarpIntegrationProvider && $integration->getProvider() !== $provider) {
            throw new NotFoundHttpException();
        }
    }

    public function assertSharedFileBelongsToIntegration(SharedFile $sharedFile, LarpIntegration $integration): void
    {
        if (!$sharedFile->getIntegration()->getId()->equals($integration->getId())) {
            throw new NotFoundHttpException();
        }
    }

    public function assertMappingBelongsToSharedFile(ObjectFieldMapping $mapping, SharedFile $sharedFile, Larp $larp): void
    {
        $this->assertSharedFileBelongsToLarp($sharedFile, $larp);

        if (!$mapping->getLarp()->getId()->equals($larp->getId())) {
            throw new NotFoundHttpException();
        }

        $mappedFile = $mapping->getExternalFile();
        if (!$mappedFile instanceof SharedFile || !$mappedFile->getId()->equals($sharedFile->getId())) {
            throw new NotFoundHttpException();
        }
    }
}
