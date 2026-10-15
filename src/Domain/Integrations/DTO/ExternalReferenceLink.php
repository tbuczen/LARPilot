<?php

namespace App\Domain\Integrations\DTO;

use App\Domain\Account\Entity\User;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\Enum\ReferenceType;

final readonly class ExternalReferenceLink
{
    public function __construct(
        public LarpIntegrationProvider $provider,
        public string $externalId,
        public ReferenceType $referenceType,
        public ReferenceRole $role = ReferenceRole::Mention,
        public ?string $name = null,
        public ?string $url = null,
        public ?string $excerpt = null,
        public ?\DateTimeImmutable $externalUpdatedAt = null,
        public ?User $linkedBy = null,
    ) {
    }
}
