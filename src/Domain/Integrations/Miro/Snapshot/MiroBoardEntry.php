<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Snapshot;

use App\Domain\Integrations\DTO\SourceContentChunk;
use App\Domain\Integrations\DTO\SyncedExternalItem;
use App\Domain\Integrations\Entity\Enum\ExternalItemAvailability;
use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use DateTimeImmutable;

final readonly class MiroBoardEntry
{
    public const TYPE_FRAME = 'frame';
    public const TYPE_CONNECTOR = 'connector';

    /**
     * @param list<string> $tags
     */
    public function __construct(
        public string $externalId,
        public ExternalItemKind $kind,
        public string $type,
        public string $title,
        public string $text,
        public array $tags,
        public string $url,
        public ?string $parentExternalId = null,
        public ?DateTimeImmutable $updatedAt = null,
        public ?string $startItemId = null,
        public ?string $endItemId = null,
    ) {
    }

    public function isFrame(): bool
    {
        return $this->kind === ExternalItemKind::Frame;
    }

    public function isConnector(): bool
    {
        return $this->kind === ExternalItemKind::Connector;
    }

    public function plainText(): string
    {
        $lines = array_filter([$this->title, $this->text !== $this->title ? $this->text : ''], static fn (string $line): bool => $line !== '');
        if ($this->tags !== []) {
            $lines[] = '#' . implode(' #', $this->tags);
        }

        return implode("\n", $lines);
    }

    public function toContentChunk(int $position): SourceContentChunk
    {
        return new SourceContentChunk(
            position: $position,
            text: $this->plainText(),
            postedAt: $this->updatedAt,
            permalink: $this->url,
        );
    }

    public function toSyncedExternalItem(ExternalItemAvailability $availability = ExternalItemAvailability::Ok): SyncedExternalItem
    {
        return new SyncedExternalItem(
            externalId: $this->externalId,
            kind: $this->kind,
            title: $this->title === '' ? null : $this->title,
            excerpt: $this->text === '' ? null : $this->text,
            url: $this->url,
            parentExternalId: $this->parentExternalId,
            externalUpdatedAt: $this->updatedAt,
            raw: array_filter([
                'type' => $this->type,
                'tags' => $this->tags,
                'startItemId' => $this->startItemId,
                'endItemId' => $this->endItemId,
            ], static fn (mixed $value): bool => $value !== null && $value !== []),
            availability: $availability,
        );
    }
}
