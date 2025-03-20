<?php

namespace App\Service\Larp;

use App\Entity\Larp;
use App\Entity\LarpIntegration;
use App\Enum\LarpIntegrationProvider;
use App\Repository\LarpIntegrationRepository;
use App\Repository\LarpRepository;

readonly class LarpManager
{

    public function __construct(
        private LarpRepository            $larpRepository,
        private LarpIntegrationRepository $larpIntegrationRepository,
    )
    {
    }

    public function getLarp(string $larpId): ?Larp
    {
        return $this->larpRepository->find($larpId);
    }

    /**
     * @return array|LarpIntegration[]
     */
    public function getIntegrationsForLarp(string $larpId): array
    {
        return $this->larpIntegrationRepository->findAllByLarp($larpId);
    }

    public function getIntegrationTypeForLarp(string $id, LarpIntegrationProvider $integration): ?LarpIntegration
    {
        return $this->larpIntegrationRepository->findOneBy(['larp' => $id, 'provider' => $integration]);
    }


}