<?php

namespace App\Domain\Integrations\Discord\Content;

use App\Domain\Integrations\Discord\Picker\DiscordSourceConfig;
use App\Domain\Integrations\Discord\Sync\DiscordSourceSync;
use App\Domain\Integrations\Discord\Sync\DiscordThreadMessages;
use App\Domain\Integrations\DTO\SourceContent;
use App\Domain\Integrations\DTO\SourceContentChunk;
use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceVisibility;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Service\SourceContentProviderInterface;

readonly class DiscordThreadDigestProvider implements SourceContentProviderInterface
{
    private const THREAD_KINDS = [ExternalItemKind::Thread, ExternalItemKind::ForumPost];

    public function __construct(
        private ExternalItemRepository $externalItemRepository,
    ) {
    }

    public function supports(ExternalReference $reference): bool
    {
        return $this->threadItem($reference) instanceof ExternalItem;
    }

    public function fetchContent(ExternalReference $reference): SourceContent
    {
        $threadItem = $this->threadItem($reference);
        if (!$threadItem instanceof ExternalItem) {
            return new SourceContent([], null, $reference->getName());
        }

        $raw = $threadItem->getRaw() ?? [];

        return new SourceContent(
            $this->chunks($threadItem, $raw),
            $threadItem->getExternalUpdatedAt(),
            $threadItem->getTitle(),
            $this->tagNames($raw),
        );
    }

    private function threadItem(ExternalReference $reference): ?ExternalItem
    {
        if ($reference->getProvider() !== LarpIntegrationProvider::Discord) {
            return null;
        }

        $threadItem = $reference->getExternalItem()
            ?? $this->externalItemRepository->findOneForLarpByExternalId($reference->getLarp(), LarpIntegrationProvider::Discord, $reference->getExternalId());
        if (!$threadItem instanceof ExternalItem || !in_array($threadItem->getKind(), self::THREAD_KINDS, true)) {
            return null;
        }

        return $threadItem;
    }

    /**
     * @param array<mixed> $raw
     * @return list<SourceContentChunk>
     */
    private function chunks(ExternalItem $threadItem, array $raw): array
    {
        $sourceConfig = DiscordSourceConfig::fromArray($threadItem->getSource()->getConfig());
        if ($sourceConfig->visibility === ExternalSourceVisibility::PlayerFacing) {
            return [];
        }

        $guildId = $threadItem->getSource()->getConfigValue('guildId') ?? $threadItem->getSource()->getIntegration()->getExternalAccountId();
        $chunks = [];
        foreach (DiscordThreadMessages::fromRaw($raw)->orderedMessages() as $message) {
            if ($message->content === '') {
                continue;
            }

            $chunks[] = new SourceContentChunk(
                count($chunks),
                $message->content,
                $message->author,
                $message->postedAt,
                is_string($guildId) ? DiscordSourceSync::messageUrl($guildId, $threadItem->getExternalId(), $message->id) : null,
            );
        }

        return $chunks;
    }

    /**
     * @param array<mixed> $raw
     * @return list<string>
     */
    private function tagNames(array $raw): array
    {
        $tagNames = [];
        foreach ($raw['appliedTags'] ?? [] as $appliedTag) {
            if (!is_array($appliedTag)) {
                continue;
            }

            $tagName = $appliedTag['name'] ?? $appliedTag['forumTagId'] ?? null;
            if (!is_string($tagName) || $tagName === '') {
                continue;
            }

            $tagNames[] = $tagName;
        }

        return $tagNames;
    }
}
