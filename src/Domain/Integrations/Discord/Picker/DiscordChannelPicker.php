<?php

namespace App\Domain\Integrations\Discord\Picker;

use App\Domain\Core\Entity\Tag;
use App\Domain\Integrations\Discord\Client\DiscordClient;
use App\Domain\Integrations\Discord\Client\Dto\DiscordChannel;
use App\Domain\Integrations\Discord\Client\Dto\DiscordChannelType;
use App\Domain\Integrations\Discord\Client\Exception\DiscordApiException;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceVisibility;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use Doctrine\ORM\EntityManagerInterface;

readonly class DiscordChannelPicker
{
    public const SELECTABLE_TARGET_TYPES = [
        TargetType::Character,
        TargetType::Faction,
        TargetType::Thread,
        TargetType::Quest,
        TargetType::Event,
        TargetType::Item,
        TargetType::Place,
        TargetType::LoreDocument,
    ];

    private const CONVERSATION_CHANNEL_TYPES = [
        DiscordChannelType::GUILD_TEXT,
        DiscordChannelType::GUILD_ANNOUNCEMENT,
        DiscordChannelType::GUILD_FORUM,
        DiscordChannelType::GUILD_MEDIA,
    ];

    public function __construct(
        private DiscordClient $discordClient,
        private ExternalSourceRepository $externalSourceRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<DiscordChannel>
     * @throws DiscordApiException
     */
    public function pickableChannels(LarpIntegration $integration): array
    {
        $guildId = $integration->getExternalAccountId();
        if ($guildId === null) {
            return [];
        }

        $channels = array_values(array_filter(
            $this->discordClient->listGuildChannels($guildId),
            static fn (DiscordChannel $channel): bool => in_array($channel->getChannelType(), self::CONVERSATION_CHANNEL_TYPES, true),
        ));
        usort($channels, static fn (DiscordChannel $left, DiscordChannel $right): int => [$left->position ?? PHP_INT_MAX, $left->id] <=> [$right->position ?? PHP_INT_MAX, $right->id]);

        return $channels;
    }

    /**
     * @return array<string, ExternalSource>
     */
    public function watchedSources(LarpIntegration $integration): array
    {
        $watchedSources = [];
        foreach ($this->externalSourceRepository->findForIntegration($integration) as $source) {
            $watchedSources[$source->getExternalId()] = $source;
        }

        return $watchedSources;
    }

    /**
     * @param list<DiscordChannel> $channels
     * @param array<mixed> $submittedChannels
     * @param list<Tag> $larpTags
     */
    public function save(LarpIntegration $integration, array $channels, array $submittedChannels, array $larpTags): void
    {
        $watchedSources = $this->watchedSources($integration);
        $larpTagIds = array_map(static fn (Tag $tag): string => $tag->getId()->toRfc4122(), $larpTags);

        foreach ($channels as $channel) {
            $submittedChannel = $submittedChannels[$channel->id] ?? [];
            if (!is_array($submittedChannel)) {
                $submittedChannel = [];
            }

            $existingSource = $watchedSources[$channel->id] ?? null;
            if (($submittedChannel['watched'] ?? null) !== '1') {
                if ($existingSource instanceof ExternalSource) {
                    $this->entityManager->remove($existingSource);
                }

                continue;
            }

            $source = $existingSource ?? (new ExternalSource())->setIntegration($integration)->setExternalId($channel->id);
            $source
                ->setKind($channel->isForum() ? ExternalSourceKind::DiscordForum : ExternalSourceKind::DiscordChannel)
                ->setTitle($channel->name ?? $channel->id)
                ->setUrl(sprintf('https://discord.com/channels/%s/%s', $integration->getExternalAccountId(), $channel->id))
                ->setEnabled(true)
                ->setConfig($this->sourceConfig($channel, $submittedChannel, $larpTagIds)->toArray() + [
                    'guildId' => $integration->getExternalAccountId(),
                    'parentId' => $channel->parentId,
                ]);
            $this->entityManager->persist($source);
        }

        $this->entityManager->flush();
    }

    /**
     * @param array<mixed> $submittedChannel
     * @param list<string> $larpTagIds
     */
    private function sourceConfig(DiscordChannel $channel, array $submittedChannel, array $larpTagIds): DiscordSourceConfig
    {
        $forumTagMappings = [];
        $submittedForumTags = $submittedChannel['forumTags'] ?? [];
        if ($channel->isForum() && is_array($submittedForumTags)) {
            foreach ($channel->availableTags as $forumTag) {
                $submittedMapping = $submittedForumTags[$forumTag->id] ?? null;
                if (!is_array($submittedMapping)) {
                    continue;
                }

                $tagId = $submittedMapping['tagId'] ?? null;
                $forumTagMappings[$forumTag->id] = new DiscordForumTagMapping(
                    is_string($tagId) && in_array($tagId, $larpTagIds, true) ? $tagId : null,
                    $this->selectableTargetType($submittedMapping['targetType'] ?? null),
                );
            }
        }

        $visibility = $submittedChannel['visibility'] ?? null;

        return new DiscordSourceConfig(
            is_string($visibility) ? ExternalSourceVisibility::tryFrom($visibility) ?? ExternalSourceVisibility::OrganizerOnly : ExternalSourceVisibility::OrganizerOnly,
            $this->selectableTargetType($submittedChannel['defaultTargetType'] ?? null),
            $forumTagMappings,
        );
    }

    private function selectableTargetType(mixed $submittedTargetType): ?TargetType
    {
        if (!is_string($submittedTargetType)) {
            return null;
        }

        $targetType = TargetType::tryFrom($submittedTargetType);
        if (!in_array($targetType, self::SELECTABLE_TARGET_TYPES, true)) {
            return null;
        }

        return $targetType;
    }
}
