<?php

namespace App\Domain\Integrations\Discord\Sync;

use App\Domain\Integrations\Discord\Client\DiscordClient;
use App\Domain\Integrations\Discord\Client\Dto\DiscordChannelType;
use App\Domain\Integrations\Discord\Client\Dto\DiscordMessage;
use App\Domain\Integrations\Discord\Client\Dto\DiscordThread;
use App\Domain\Integrations\Discord\Client\Exception\DiscordAccessLostException;
use App\Domain\Integrations\Discord\Client\Exception\DiscordApiException;
use App\Domain\Integrations\Discord\Client\Exception\DiscordRateLimitedException;
use App\Domain\Integrations\Discord\Content\DiscordContentSanitizer;
use App\Domain\Integrations\Discord\Picker\DiscordSourceConfig;
use App\Domain\Integrations\DTO\SourceSyncPage;
use App\Domain\Integrations\DTO\SyncedExternalItem;
use App\Domain\Integrations\Entity\Enum\ExternalItemAvailability;
use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceVisibility;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Service\Exceptions\SourceSyncForbiddenException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncRateLimitedException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncUnauthorizedException;
use App\Domain\Integrations\Service\ExcerptNormalizer;

readonly class DiscordSourceSync
{
    public const MAX_ARCHIVED_THREADS_PER_PAGE = 200;
    public const MAX_MESSAGE_FETCHES_PER_PAGE = 20;
    public const RECENT_MESSAGE_COUNT = 3;
    public const EXCERPT_MESSAGE_LENGTH = 160;
    public const SYNC_RATE_LIMIT_ATTEMPTS = 1;

    private const SKIPPED_THREAD_TYPES = [DiscordChannelType::PRIVATE_THREAD];

    private DiscordClient $discordClient;

    public function __construct(DiscordClient $discordClient)
    {
        $this->discordClient = $discordClient->withRateLimitAttempts(self::SYNC_RATE_LIMIT_ATTEMPTS);
    }

    public static function threadUrl(string $guildId, string $threadId): string
    {
        return sprintf('https://discord.com/channels/%s/%s', $guildId, $threadId);
    }

    public static function messageUrl(string $guildId, string $threadId, string $messageId): string
    {
        return sprintf('https://discord.com/channels/%s/%s/%s', $guildId, $threadId, $messageId);
    }

    public static function isWatchedThread(DiscordThread $thread, string $channelId): bool
    {
        if ($thread->parentId !== $channelId) {
            return false;
        }

        return !in_array(DiscordChannelType::tryFrom($thread->type), self::SKIPPED_THREAD_TYPES, true);
    }

    /**
     * @param array<string, mixed>|null $cursor
     */
    public function fetchSourcePage(ExternalSource $source, ?array $cursor): SourceSyncPage
    {
        try {
            return $this->syncSource($source, DiscordSyncCursor::fromArray($cursor));
        } catch (DiscordRateLimitedException $rateLimited) {
            throw new SourceSyncRateLimitedException((int) ceil($rateLimited->getRetryAfterSeconds()), $rateLimited);
        } catch (DiscordAccessLostException $accessLost) {
            if ($accessLost->getCode() === 401) {
                throw new SourceSyncUnauthorizedException($accessLost->getMessage(), $accessLost);
            }

            throw new SourceSyncForbiddenException($accessLost->getMessage(), $accessLost);
        }
    }

    private function syncSource(ExternalSource $source, DiscordSyncCursor $cursor): SourceSyncPage
    {
        $guildId = $source->getConfigValue('guildId') ?? $source->getIntegration()->getExternalAccountId();
        if (!is_string($guildId) || $guildId === '') {
            throw new SourceSyncForbiddenException('The Discord server of this source is unknown.');
        }

        $sourceConfig = DiscordSourceConfig::fromArray($source->getConfig());
        $existingItems = $this->existingItems($source);

        $threads = [];
        if (!$cursor->isBackfilling()) {
            foreach ($this->discordClient->listActiveThreads($guildId) as $activeThread) {
                if (!self::isWatchedThread($activeThread, $source->getExternalId())) {
                    continue;
                }

                $threads[$activeThread->id] = $activeThread;
            }
        }

        [$archivedThreads, $nextCursor, $archiveComplete] = $this->archivedThreads($source, $cursor);
        foreach ($archivedThreads as $archivedThread) {
            $threads[$archivedThread->id] ??= $archivedThread;
        }

        $forumTagNames = $this->forumTagNames($source, $guildId, $threads);
        $messageFetches = 0;
        $messageBacklog = false;
        $syncedItems = [];
        foreach ($threads as $thread) {
            $existingItem = $existingItems[$thread->id] ?? null;
            $threadMessages = DiscordThreadMessages::fromRaw($existingItem?->getRaw() ?? []);
            $availability = $thread->archived ? ExternalItemAvailability::Archived : ExternalItemAvailability::Ok;

            if ($sourceConfig->visibility === ExternalSourceVisibility::PlayerFacing) {
                $threadMessages = new DiscordThreadMessages();
            } elseif ($this->hasNewMessages($thread, $threadMessages)) {
                if ($messageFetches >= self::MAX_MESSAGE_FETCHES_PER_PAGE) {
                    $messageBacklog = true;
                } else {
                    $messageFetches++;
                    try {
                        $threadMessages = $this->fetchThreadMessages($thread, $threadMessages);
                    } catch (DiscordApiException $failure) {
                        if ($failure->getCode() !== 404) {
                            throw $failure;
                        }

                        $availability = ExternalItemAvailability::Gone;
                    }
                }
            }

            $syncedItems[] = $this->syncedThread($source, $sourceConfig, $guildId, $thread, $threadMessages, $forumTagNames, $availability);
        }

        if (!$cursor->isBackfilling()) {
            foreach ($existingItems as $externalId => $existingItem) {
                if (isset($threads[$externalId])) {
                    continue;
                }

                $unlistedItem = $this->unlistedItem($existingItem, $sourceConfig);
                if ($unlistedItem === null) {
                    continue;
                }

                $syncedItems[] = $unlistedItem;
            }
        }

        return new SourceSyncPage($syncedItems, $nextCursor->toArray(), !$archiveComplete || $messageBacklog);
    }

    /**
     * @return array{0: list<DiscordThread>, 1: DiscordSyncCursor, 2: bool}
     */
    private function archivedThreads(ExternalSource $source, DiscordSyncCursor $cursor): array
    {
        $archivedThreads = [];
        $oldestArchived = null;
        $newestArchived = null;

        try {
            foreach ($this->discordClient->listArchivedPublicThreads($source->getExternalId(), $cursor->backfillBefore) as $archivedThread) {
                if ($this->isAlreadySynced($archivedThread, $cursor)) {
                    break;
                }

                if ($archivedThread->archiveTimestamp !== null) {
                    $oldestArchived = $oldestArchived === null ? $archivedThread->archiveTimestamp : min($oldestArchived, $archivedThread->archiveTimestamp);
                    $newestArchived = DiscordSyncCursor::latest($newestArchived, $archivedThread->archiveTimestamp);
                }

                if (self::isWatchedThread($archivedThread, $source->getExternalId())) {
                    $archivedThreads[] = $archivedThread;
                }

                if (count($archivedThreads) >= self::MAX_ARCHIVED_THREADS_PER_PAGE && $oldestArchived !== null) {
                    return [$archivedThreads, $cursor->continueBackfill($oldestArchived, $newestArchived), false];
                }
            }
        } catch (DiscordApiException $failure) {
            if ($failure->getCode() === 404) {
                throw new SourceSyncForbiddenException('The Discord channel of this source no longer exists.', $failure);
            }

            throw $failure;
        }

        return [$archivedThreads, $cursor->completeBackfill($newestArchived), true];
    }

    private function isAlreadySynced(DiscordThread $archivedThread, DiscordSyncCursor $cursor): bool
    {
        if ($cursor->archivedThrough === null || $archivedThread->archiveTimestamp === null) {
            return false;
        }

        return $archivedThread->archiveTimestamp <= $cursor->archivedThrough;
    }

    private function hasNewMessages(DiscordThread $thread, DiscordThreadMessages $threadMessages): bool
    {
        if ($thread->lastMessageId === null) {
            return false;
        }

        return $thread->lastMessageId !== $threadMessages->syncedLastMessageId;
    }

    private function fetchThreadMessages(DiscordThread $thread, DiscordThreadMessages $threadMessages): DiscordThreadMessages
    {
        if ($threadMessages->cursor === null) {
            return $this->freshThreadMessages($thread);
        }

        $newMessages = [];
        foreach ($this->discordClient->listMessages($thread->id, $threadMessages->cursor) as $newMessage) {
            $newMessages[] = DiscordStoredMessage::fromMessage($newMessage);
            if (count($newMessages) >= DiscordClient::MAX_PAGE_SIZE) {
                break;
            }
        }

        $recentMessages = count($newMessages) >= DiscordClient::MAX_PAGE_SIZE
            ? $this->latestMessages($thread)
            : array_slice(array_merge($threadMessages->recentMessages, $newMessages), -self::RECENT_MESSAGE_COUNT);

        return new DiscordThreadMessages(
            $thread->lastMessageId,
            $this->newestMessageId($recentMessages) ?? $threadMessages->cursor,
            $threadMessages->starterMessage,
            $recentMessages,
        );
    }

    private function freshThreadMessages(DiscordThread $thread): DiscordThreadMessages
    {
        $starterMessage = null;
        foreach ($this->discordClient->listMessages($thread->id, null, 1) as $firstMessage) {
            $starterMessage = DiscordStoredMessage::fromMessage($firstMessage);
            break;
        }

        $recentMessages = $this->latestMessages($thread);

        return new DiscordThreadMessages(
            $thread->lastMessageId,
            $this->newestMessageId($recentMessages) ?? $starterMessage?->id,
            $starterMessage,
            $recentMessages,
        );
    }

    /**
     * @return list<DiscordStoredMessage>
     */
    private function latestMessages(DiscordThread $thread): array
    {
        return array_map(
            static fn (DiscordMessage $message): DiscordStoredMessage => DiscordStoredMessage::fromMessage($message),
            $this->discordClient->listLatestMessages($thread->id, self::RECENT_MESSAGE_COUNT),
        );
    }

    /**
     * @param list<DiscordStoredMessage> $messages
     */
    private function newestMessageId(array $messages): ?string
    {
        $newestMessageId = null;
        foreach ($messages as $message) {
            if ($newestMessageId === null || DiscordSnowflake::compare($message->id, $newestMessageId) > 0) {
                $newestMessageId = $message->id;
            }
        }

        return $newestMessageId;
    }

    /**
     * @param array<string, string> $forumTagNames
     */
    private function syncedThread(
        ExternalSource $source,
        DiscordSourceConfig $sourceConfig,
        string $guildId,
        DiscordThread $thread,
        DiscordThreadMessages $threadMessages,
        array $forumTagNames,
        ExternalItemAvailability $availability,
    ): SyncedExternalItem {
        $appliedTags = [];
        $targetType = null;
        foreach ($thread->appliedTags as $forumTagId) {
            $forumTagMapping = $sourceConfig->forumTagMapping($forumTagId);
            $targetType ??= $forumTagMapping->targetType;
            $appliedTags[] = [
                'forumTagId' => $forumTagId,
                'name' => $forumTagNames[$forumTagId] ?? null,
                'tagId' => $forumTagMapping->tagId,
                'targetType' => $forumTagMapping->targetType?->value,
            ];
        }

        $raw = [
            'threadType' => $thread->type,
            'parentId' => $thread->parentId,
            'messageCount' => $thread->messageCount,
            'lastMessageId' => $thread->lastMessageId,
            'archived' => $thread->archived,
            'locked' => $thread->locked,
            'archiveTimestamp' => $thread->archiveTimestamp?->format(DiscordSyncCursor::TIMESTAMP_FORMAT),
            'appliedTags' => $appliedTags,
            'targetType' => ($targetType ?? $sourceConfig->defaultTargetType)?->value,
        ];
        if ($sourceConfig->visibility === ExternalSourceVisibility::OrganizerOnly) {
            $raw[DiscordThreadMessages::RAW_KEY] = $threadMessages->toArray();
        }

        $lastActivityAt = $thread->lastMessageId !== null ? DiscordSnowflake::createdAt($thread->lastMessageId) : null;

        return new SyncedExternalItem(
            externalId: $thread->id,
            kind: $source->getKind() === ExternalSourceKind::DiscordForum ? ExternalItemKind::ForumPost : ExternalItemKind::Thread,
            title: $thread->name ?? $thread->id,
            excerpt: $sourceConfig->visibility === ExternalSourceVisibility::OrganizerOnly ? $this->excerpt($threadMessages) : null,
            author: $sourceConfig->visibility === ExternalSourceVisibility::OrganizerOnly ? $threadMessages->starterMessage?->author : null,
            url: self::threadUrl($guildId, $thread->id),
            parentExternalId: $thread->parentId,
            externalUpdatedAt: $lastActivityAt ?? $thread->archiveTimestamp,
            raw: $raw,
            availability: $availability,
        );
    }

    private function excerpt(DiscordThreadMessages $threadMessages): ?string
    {
        $excerptParts = [];
        foreach ($threadMessages->orderedMessages() as $message) {
            $content = DiscordContentSanitizer::sanitize($message->content, [], self::EXCERPT_MESSAGE_LENGTH);
            if ($content === '') {
                continue;
            }

            $excerptParts[] = $message === $threadMessages->starterMessage ? $content : $message->author . ': ' . $content;
        }

        return ExcerptNormalizer::normalize(implode("\n", $excerptParts));
    }

    private function unlistedItem(ExternalItem $existingItem, DiscordSourceConfig $sourceConfig): ?SyncedExternalItem
    {
        $isPlayerFacing = $sourceConfig->visibility === ExternalSourceVisibility::PlayerFacing;
        $raw = $existingItem->getRaw() ?? [];
        $holdsContent = $existingItem->getExcerpt() !== null || $existingItem->getAuthor() !== null || isset($raw[DiscordThreadMessages::RAW_KEY]);
        $wasActive = $existingItem->getAvailability() === ExternalItemAvailability::Ok;

        if (!$wasActive && !($isPlayerFacing && $holdsContent)) {
            return null;
        }

        if ($isPlayerFacing) {
            unset($raw[DiscordThreadMessages::RAW_KEY]);
        }

        return new SyncedExternalItem(
            externalId: $existingItem->getExternalId(),
            kind: $existingItem->getKind(),
            title: $existingItem->getTitle(),
            excerpt: $isPlayerFacing ? null : $existingItem->getExcerpt(),
            author: $isPlayerFacing ? null : $existingItem->getAuthor(),
            url: $existingItem->getUrl(),
            parentExternalId: $existingItem->getParentExternalId(),
            externalUpdatedAt: $existingItem->getExternalUpdatedAt(),
            raw: $raw,
            availability: $wasActive ? ExternalItemAvailability::Gone : $existingItem->getAvailability(),
        );
    }

    /**
     * @param array<string, DiscordThread> $threads
     * @return array<string, string>
     */
    private function forumTagNames(ExternalSource $source, string $guildId, array $threads): array
    {
        if ($source->getKind() !== ExternalSourceKind::DiscordForum) {
            return [];
        }

        $hasAppliedTags = array_filter($threads, static fn (DiscordThread $thread): bool => $thread->appliedTags !== []) !== [];
        if (!$hasAppliedTags) {
            return [];
        }

        $forumTagNames = [];
        foreach ($this->discordClient->listGuildChannels($guildId) as $channel) {
            if ($channel->id !== $source->getExternalId()) {
                continue;
            }

            foreach ($channel->availableTags as $forumTag) {
                $forumTagNames[$forumTag->id] = $forumTag->name;
            }
        }

        return $forumTagNames;
    }

    /**
     * @return array<string, ExternalItem>
     */
    private function existingItems(ExternalSource $source): array
    {
        $existingItems = [];
        foreach ($source->getItems() as $existingItem) {
            if (!in_array($existingItem->getKind(), [ExternalItemKind::Thread, ExternalItemKind::ForumPost], true)) {
                continue;
            }

            $existingItems[$existingItem->getExternalId()] = $existingItem;
        }

        return $existingItems;
    }
}
