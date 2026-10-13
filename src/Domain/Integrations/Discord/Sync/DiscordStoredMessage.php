<?php

namespace App\Domain\Integrations\Discord\Sync;

use App\Domain\Integrations\Discord\Client\Dto\DiscordMessage;
use App\Domain\Integrations\Discord\Content\DiscordContentSanitizer;

final readonly class DiscordStoredMessage
{
    public function __construct(
        public string $id,
        public string $author,
        public string $content,
        public ?\DateTimeImmutable $postedAt,
    ) {
    }

    public static function fromMessage(DiscordMessage $message): self
    {
        return new self(
            $message->id,
            $message->author->getDisplayName(),
            DiscordContentSanitizer::sanitize($message->content, $message->mentionedUserNames),
            $message->timestamp,
        );
    }

    /**
     * @param array<mixed> $storedMessage
     */
    public static function fromArray(array $storedMessage): ?self
    {
        $id = $storedMessage['id'] ?? null;
        if (!is_string($id) || $id === '') {
            return null;
        }

        $postedAt = $storedMessage['postedAt'] ?? null;

        return new self(
            $id,
            (string) ($storedMessage['author'] ?? ''),
            (string) ($storedMessage['content'] ?? ''),
            is_string($postedAt) ? new \DateTimeImmutable($postedAt) : null,
        );
    }

    /**
     * @return array{id: string, author: string, content: string, postedAt: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author,
            'content' => $this->content,
            'postedAt' => $this->postedAt?->format(\DateTimeInterface::RFC3339_EXTENDED),
        ];
    }
}
