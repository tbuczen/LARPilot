<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Snapshot;

use App\Domain\Integrations\DTO\SourceContent;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Service\SourceContentProviderInterface;

final readonly class MiroSourceContentProvider implements SourceContentProviderInterface
{
    public function __construct(
        private MiroBoardSnapshotProvider $snapshotProvider,
    ) {
    }

    public function supports(ExternalReference $reference): bool
    {
        return $reference->getProvider() === LarpIntegrationProvider::Miro && $this->boardOf($reference) instanceof ExternalSource;
    }

    public function fetchContent(ExternalReference $reference): SourceContent
    {
        $board = $this->boardOf($reference);
        if (!$board instanceof ExternalSource) {
            return new SourceContent([]);
        }

        $snapshot = $this->snapshotProvider->snapshot($board->getIntegration(), $board->getExternalId());
        $entries = $this->entriesFor($reference, $snapshot);

        $chunks = [];
        foreach ($entries as $position => $entry) {
            $chunks[] = $entry->toContentChunk($position);
        }

        return new SourceContent($chunks, $this->latestUpdate($entries));
    }

    /**
     * @return list<MiroBoardEntry>
     */
    private function entriesFor(ExternalReference $reference, MiroBoardSnapshot $snapshot): array
    {
        if ($reference->getReferenceType() === ReferenceType::Board) {
            return $snapshot->entries;
        }

        $entry = $snapshot->entry($reference->getExternalId());
        if (!$entry instanceof MiroBoardEntry) {
            return [];
        }

        if (!$entry->isFrame()) {
            return [$entry];
        }

        return [$entry, ...$snapshot->itemsInFrame($entry->externalId)];
    }

    /**
     * @param list<MiroBoardEntry> $entries
     */
    private function latestUpdate(array $entries): ?\DateTimeImmutable
    {
        $latestUpdate = null;
        foreach ($entries as $entry) {
            if ($entry->updatedAt !== null && ($latestUpdate === null || $entry->updatedAt > $latestUpdate)) {
                $latestUpdate = $entry->updatedAt;
            }
        }

        return $latestUpdate;
    }

    private function boardOf(ExternalReference $reference): ?ExternalSource
    {
        return $reference->getSource() ?? $reference->getExternalItem()?->getSource();
    }
}
