<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Browser;

use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardSnapshot;

final readonly class MiroBoardBrowserView
{
    /**
     * @param list<MiroBoardFrameGroup> $frameGroups
     * @param list<MiroBoardEntry> $looseItems
     * @param list<MiroBoardEntry> $connectors
     * @param array<string, ExternalItem> $storedItems
     * @param array<string, list<ExternalReference>> $referencesByExternalId
     * @param list<ExternalItem> $missingItems
     */
    public function __construct(
        public MiroBoardSnapshot $snapshot,
        public array $frameGroups,
        public array $looseItems,
        public array $connectors,
        public array $storedItems,
        public array $referencesByExternalId,
        public array $missingItems,
    ) {
    }

    public function storedItem(string $externalId): ?ExternalItem
    {
        return $this->storedItems[$externalId] ?? null;
    }

    /**
     * @return list<ExternalReference>
     */
    public function references(string $externalId): array
    {
        return $this->referencesByExternalId[$externalId] ?? [];
    }
}
