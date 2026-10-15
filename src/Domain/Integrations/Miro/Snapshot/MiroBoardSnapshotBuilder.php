<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Snapshot;

use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Miro\Client\MiroApiClient;
use App\Domain\Integrations\Miro\DTO\MiroConnector;
use App\Domain\Integrations\Miro\DTO\MiroItem;
use Psr\Clock\ClockInterface;

final readonly class MiroBoardSnapshotBuilder
{
    public const SUPPORTED_ITEM_TYPES = ['frame', 'card', 'sticky_note', 'shape', 'text', 'app_card'];

    private const TITLE_MAX_LENGTH = 120;

    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    public function build(MiroApiClient $miroApiClient, string $boardId): MiroBoardSnapshot
    {
        $tagTitles = [];
        foreach ($miroApiClient->iterateTags($boardId) as $tag) {
            $tagTitles[$tag->id] = $tag->title;
        }

        $miroItems = $this->collectItems($miroApiClient, $boardId);

        $entries = [];
        foreach ($miroItems as $miroItem) {
            $entries[] = $this->itemEntry($boardId, $miroItem, $tagTitles);
        }

        $entryTitles = [];
        foreach ($entries as $entry) {
            $entryTitles[$entry->externalId] = $entry->title;
        }

        foreach ($miroApiClient->iterateConnectors($boardId) as $miroConnector) {
            $entries[] = $this->connectorEntry($boardId, $miroConnector, $entryTitles);
        }

        return new MiroBoardSnapshot($boardId, $entries, $this->clock->now());
    }

    /**
     * @return array<string, MiroItem>
     */
    private function collectItems(MiroApiClient $miroApiClient, string $boardId): array
    {
        $miroItems = [];
        foreach ($miroApiClient->iterateItems($boardId) as $miroItem) {
            if (!in_array($miroItem->type, self::SUPPORTED_ITEM_TYPES, true)) {
                continue;
            }
            $miroItems[$miroItem->id] = $miroItem;
        }

        $frames = array_filter($miroItems, static fn (MiroItem $miroItem): bool => $miroItem->type === MiroBoardEntry::TYPE_FRAME);
        foreach ($frames as $frame) {
            foreach ($miroApiClient->iterateItems($boardId, parentItemId: $frame->id) as $childItem) {
                if (!in_array($childItem->type, self::SUPPORTED_ITEM_TYPES, true)) {
                    continue;
                }
                $knownItem = $miroItems[$childItem->id] ?? null;
                if ($knownItem instanceof MiroItem && $knownItem->parentId !== null) {
                    continue;
                }
                $miroItems[$childItem->id] = $childItem->parentId === null ? $this->withParent($childItem, $frame->id) : $childItem;
            }
        }

        return $miroItems;
    }

    /**
     * @param array<string, string> $tagTitles
     */
    private function itemEntry(string $boardId, MiroItem $miroItem, array $tagTitles): MiroBoardEntry
    {
        $isFrame = $miroItem->type === MiroBoardEntry::TYPE_FRAME;
        $hasOwnTitle = $isFrame || in_array($miroItem->type, ['card', 'app_card'], true);
        $text = $isFrame ? '' : ($miroItem->plainContent ?? '');
        $title = $hasOwnTitle ? $this->plainText($miroItem->title ?? '') : $this->firstLine($text);

        $tags = [];
        foreach ($miroItem->tagIds as $tagId) {
            if (isset($tagTitles[$tagId])) {
                $tags[] = $tagTitles[$tagId];
            }
        }

        return new MiroBoardEntry(
            externalId: $miroItem->id,
            kind: $isFrame ? ExternalItemKind::Frame : ExternalItemKind::BoardItem,
            type: $miroItem->type,
            title: $title,
            text: $text,
            tags: $tags,
            url: MiroBoardUrls::item($boardId, $miroItem->id),
            parentExternalId: $isFrame ? null : $miroItem->parentId,
            updatedAt: $miroItem->modifiedAt ?? $miroItem->createdAt,
        );
    }

    /**
     * @param array<string, string> $entryTitles
     */
    private function connectorEntry(string $boardId, MiroConnector $miroConnector, array $entryTitles): MiroBoardEntry
    {
        $caption = $this->plainText($miroConnector->getCaptionText());
        $startTitle = $this->endpointTitle($miroConnector->startItemId, $entryTitles);
        $endTitle = $this->endpointTitle($miroConnector->endItemId, $entryTitles);
        $title = $caption === ''
            ? sprintf('%s → %s', $startTitle, $endTitle)
            : sprintf('%s — %s → %s', $startTitle, $caption, $endTitle);

        return new MiroBoardEntry(
            externalId: $miroConnector->id,
            kind: ExternalItemKind::Connector,
            type: MiroBoardEntry::TYPE_CONNECTOR,
            title: $title,
            text: $caption,
            tags: [],
            url: MiroBoardUrls::item($boardId, $miroConnector->id),
            updatedAt: $miroConnector->modifiedAt ?? $miroConnector->createdAt,
            startItemId: $miroConnector->startItemId,
            endItemId: $miroConnector->endItemId,
        );
    }

    /**
     * @param array<string, string> $entryTitles
     */
    private function endpointTitle(?string $itemId, array $entryTitles): string
    {
        if ($itemId === null) {
            return '?';
        }

        $endpointTitle = $entryTitles[$itemId] ?? '';

        return $endpointTitle === '' ? $itemId : $endpointTitle;
    }

    private function withParent(MiroItem $miroItem, string $parentId): MiroItem
    {
        return new MiroItem(
            id: $miroItem->id,
            type: $miroItem->type,
            title: $miroItem->title,
            htmlContent: $miroItem->htmlContent,
            plainContent: $miroItem->plainContent,
            position: $miroItem->position,
            geometry: $miroItem->geometry,
            parentId: $parentId,
            createdAt: $miroItem->createdAt,
            modifiedAt: $miroItem->modifiedAt,
            tagIds: $miroItem->tagIds,
        );
    }

    private function plainText(string $html): string
    {
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function firstLine(string $text): string
    {
        $firstLine = trim(strtok($text, "\n") ?: '');
        if (mb_strlen($firstLine) <= self::TITLE_MAX_LENGTH) {
            return $firstLine;
        }

        return rtrim(mb_substr($firstLine, 0, self::TITLE_MAX_LENGTH - 1)) . '…';
    }
}
