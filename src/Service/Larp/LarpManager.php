<?php

namespace App\Service\Larp;

use App\Entity\Enum\LarpIntegrationProvider;
use App\Entity\Larp;
use App\Entity\LarpIntegration;
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
     * @return LarpIntegration[]
     */
    public function getIntegrationsForLarp(string|Larp $larp): array
    {
        return $this->larpIntegrationRepository->findAllByLarp($larp);
    }

    public function getIntegrationTypeForLarp(string|Larp $larp, LarpIntegrationProvider $integration): ?LarpIntegration
    {
        return $this->larpIntegrationRepository->findOneBy(['larp' => $larp, 'provider' => $integration]);
    }

    public function getLarpCharacters(Larp $larp)
    {


    }


}