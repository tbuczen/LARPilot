<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Snapshot;

use App\Domain\Integrations\DTO\SourceSyncPage;
use App\Domain\Integrations\Entity\Enum\ExternalItemAvailability;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;
use DateTimeInterface;

final readonly class MiroBoardSync
{
    public function __construct(
        private MiroBoardSnapshotProvider $snapshotProvider,
        private ExternalItemRepository $externalItemRepository,
        private ExternalReferenceRepository $externalReferenceRepository,
    ) {
    }

    public function fetchPage(ExternalSource $board): SourceSyncPage
    {
        $snapshot = $this->snapshotProvider->snapshot($board->getIntegration(), $board->getExternalId());
        $storedItems = $this->externalItemRepository->findForSourceIndexedByExternalId($board);
        $linkedNames = $this->externalReferenceRepository->findLinkedNamesByExternalItem($board);

        $syncedItems = [];
        foreach ($snapshot->entries as $entry) {
            $storedItem = $storedItems[$entry->externalId] ?? null;
            $syncedItems[] = $entry->toSyncedExternalItem($this->availabilityOf($entry, $storedItem, $linkedNames));
        }

        $missingExternalIds = [];
        foreach ($storedItems as $externalId => $storedItem) {
            if ($snapshot->entry((string) $externalId) === null && $storedItem->getAvailability() !== ExternalItemAvailability::Gone) {
                $missingExternalIds[] = (string) $externalId;
            }
        }

        return new SourceSyncPage(
            items: $syncedItems,
            nextCursor: [
                'snapshotBuiltAt' => $snapshot->builtAt->format(DateTimeInterface::ATOM),
                'itemCount' => count($snapshot->entries),
                'contentHash' => $snapshot->contentHash(),
            ],
            hasMorePages: false,
            missingExternalIds: $missingExternalIds,
        );
    }

    /**
     * @param array<string, list<string>> $linkedNames
     */
    private function availabilityOf(MiroBoardEntry $entry, ?ExternalItem $storedItem, array $linkedNames): ExternalItemAvailability
    {
        if (!$storedItem instanceof ExternalItem) {
            return ExternalItemAvailability::Ok;
        }

        $newTitle = $entry->title === '' ? null : $entry->title;
        if ($storedItem->getTitle() !== null && $storedItem->getTitle() !== $newTitle) {
            return ExternalItemAvailability::Changed;
        }

        if ($storedItem->getAvailability() !== ExternalItemAvailability::Changed) {
            return ExternalItemAvailability::Ok;
        }

        foreach ($linkedNames[$storedItem->getId()->toRfc4122()] ?? [] as $linkedName) {
            if ($linkedName !== $newTitle) {
                return ExternalItemAvailability::Changed;
            }
        }

        return ExternalItemAvailability::Ok;
    }
}
