<?php

namespace App\Domain\Integrations\DTO;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceType;

final readonly class ParsedExternalUrl
{
    public function __construct(
        public LarpIntegrationProvider $provider,
        public string $externalId,
        public ReferenceType $referenceType,
        public string $url,
    ) {
    }
}
