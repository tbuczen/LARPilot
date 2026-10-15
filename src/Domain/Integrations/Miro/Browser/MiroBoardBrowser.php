<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Browser;

use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardSnapshot;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;

final readonly class MiroBoardBrowser
{
    public function __construct(
        private ExternalItemRepository $externalItemRepository,
        private ExternalReferenceRepository $externalReferenceRepository,
    ) {
    }

    public function view(ExternalSource $board, MiroBoardSnapshot $snapshot): MiroBoardBrowserView
    {
        $frameGroups = [];
        foreach ($snapshot->frames() as $frame) {
            $frameGroups[] = new MiroBoardFrameGroup($frame, $snapshot->itemsInFrame($frame->externalId));
        }

        $storedItems = $this->externalItemRepository->findForSourceIndexedByExternalId($board);
        $missingItems = array_values(array_filter(
            $storedItems,
            static fn (ExternalItem $storedItem): bool => $snapshot->entry($storedItem->getExternalId()) === null,
        ));

        $referencedExternalIds = [$board->getExternalId(), ...$snapshot->externalIds()];
        $referencesByExternalId = [];
        foreach ($this->externalReferenceRepository->findForSourceOrExternalIds($board, $referencedExternalIds) as $reference) {
            if ($reference->getStoryObject() === null) {
                continue;
            }
            $referencesByExternalId[$reference->getExternalId()][] = $reference;
        }

        return new MiroBoardBrowserView(
            snapshot: $snapshot,
            frameGroups: $frameGroups,
            looseItems: $snapshot->itemsOutsideFrames(),
            connectors: $snapshot->connectors(),
            storedItems: $storedItems,
            referencesByExternalId: $referencesByExternalId,
            missingItems: $missingItems,
        );
    }
}
