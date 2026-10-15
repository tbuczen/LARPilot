<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Snapshot;

use DateTimeImmutable;

final readonly class MiroBoardSnapshot
{
    /**
     * @var array<string, MiroBoardEntry>
     */
    private array $entriesById;

    /**
     * @param list<MiroBoardEntry> $entries
     */
    public function __construct(
        public string $boardId,
        public array $entries,
        public DateTimeImmutable $builtAt,
    ) {
        $entriesById = [];
        foreach ($entries as $entry) {
            $entriesById[$entry->externalId] = $entry;
        }
        $this->entriesById = $entriesById;
    }

    public function entry(string $externalId): ?MiroBoardEntry
    {
        return $this->entriesById[$externalId] ?? null;
    }

    /**
     * @return list<string>
     */
    public function externalIds(): array
    {
        return array_map(static fn (MiroBoardEntry $entry): string => $entry->externalId, array_values($this->entriesById));
    }

    /**
     * @return list<MiroBoardEntry>
     */
    public function frames(): array
    {
        return array_values(array_filter($this->entries, static fn (MiroBoardEntry $entry): bool => $entry->isFrame()));
    }

    /**
     * @return list<MiroBoardEntry>
     */
    public function connectors(): array
    {
        return array_values(array_filter($this->entries, static fn (MiroBoardEntry $entry): bool => $entry->isConnector()));
    }

    /**
     * @return list<MiroBoardEntry>
     */
    public function itemsInFrame(string $frameId): array
    {
        return array_values(array_filter(
            $this->entries,
            static fn (MiroBoardEntry $entry): bool => !$entry->isFrame() && !$entry->isConnector() && $entry->parentExternalId === $frameId,
        ));
    }

    /**
     * @return list<MiroBoardEntry>
     */
    public function itemsOutsideFrames(): array
    {
        return array_values(array_filter(
            $this->entries,
            fn (MiroBoardEntry $entry): bool => !$entry->isFrame()
                && !$entry->isConnector()
                && ($entry->parentExternalId === null || !$this->entry($entry->parentExternalId)?->isFrame()),
        ));
    }

    public function contentHash(): string
    {
        return hash('sha256', implode("\n", array_map(
            static fn (MiroBoardEntry $entry): string => $entry->externalId . ':' . $entry->plainText(),
            $this->entries,
        )));
    }
}
