<?php

namespace App\Domain\Integrations\Discord\Sync;

final readonly class DiscordThreadMessages
{
    public const RAW_KEY = 'messages';

    /**
     * @param list<DiscordStoredMessage> $recentMessages
     */
    public function __construct(
        public ?string $syncedLastMessageId = null,
        public ?string $cursor = null,
        public ?DiscordStoredMessage $starterMessage = null,
        public array $recentMessages = [],
    ) {
    }

    /**
     * @param array<mixed> $raw
     */
    public static function fromRaw(array $raw): self
    {
        $storedMessages = $raw[self::RAW_KEY] ?? null;
        if (!is_array($storedMessages)) {
            return new self();
        }

        $syncedLastMessageId = $storedMessages['syncedLastMessageId'] ?? null;
        $cursor = $storedMessages['cursor'] ?? null;
        $starterMessage = $storedMessages['starter'] ?? null;
        $recentMessages = [];
        foreach ($storedMessages['recent'] ?? [] as $recentMessage) {
            $storedMessage = is_array($recentMessage) ? DiscordStoredMessage::fromArray($recentMessage) : null;
            if ($storedMessage === null) {
                continue;
            }

            $recentMessages[] = $storedMessage;
        }

        return new self(
            is_string($syncedLastMessageId) ? $syncedLastMessageId : null,
            is_string($cursor) ? $cursor : null,
            is_array($starterMessage) ? DiscordStoredMessage::fromArray($starterMessage) : null,
            $recentMessages,
        );
    }

    public function hasContent(): bool
    {
        return $this->starterMessage !== null || $this->recentMessages !== [];
    }

    /**
     * @return list<DiscordStoredMessage>
     */
    public function orderedMessages(): array
    {
        $messages = [];
        if ($this->starterMessage !== null) {
            $messages[] = $this->starterMessage;
        }

        foreach ($this->recentMessages as $recentMessage) {
            if ($recentMessage->id === $this->starterMessage?->id) {
                continue;
            }

            $messages[] = $recentMessage;
        }

        return $messages;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'syncedLastMessageId' => $this->syncedLastMessageId,
            'cursor' => $this->cursor,
            'starter' => $this->starterMessage?->toArray(),
            'recent' => array_map(static fn (DiscordStoredMessage $message): array => $message->toArray(), $this->recentMessages),
        ];
    }
}
