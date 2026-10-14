<?php

namespace App\Domain\Integrations\DTO;

use App\Domain\Integrations\Entity\Enum\ExternalItemAvailability;
use App\Domain\Integrations\Entity\Enum\ExternalItemKind;

final readonly class SyncedExternalItem
{
    /**
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public string $externalId,
        public ExternalItemKind $kind,
        public ?string $title = null,
        public ?string $excerpt = null,
        public ?string $author = null,
        public ?string $url = null,
        public ?string $parentExternalId = null,
        public ?\DateTimeImmutable $externalUpdatedAt = null,
        public ?array $raw = null,
        public ExternalItemAvailability $availability = ExternalItemAvailability::Ok,
    ) {
    }
}
