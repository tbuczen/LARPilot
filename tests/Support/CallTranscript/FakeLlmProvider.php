<?php

declare(strict_types=1);

namespace Tests\Support\CallTranscript;

use App\Domain\StoryAI\DTO\ChatMessage;
use App\Domain\StoryAI\Service\Provider\LLMProviderInterface;
use RuntimeException;

final class FakeLlmProvider implements LLMProviderInterface
{
    public const DEFAULT_CONTEXT_LENGTH = 128000;

    /**
     * @var list<list<ChatMessage>>
     */
    public static array $calls = [];

    /**
     * @var list<string>
     */
    public static array $responses = [];

    public static ?string $failure = null;

    public static int $contextLength = self::DEFAULT_CONTEXT_LENGTH;

    public static function reset(): void
    {
        self::$calls = [];
        self::$responses = [];
        self::$failure = null;
        self::$contextLength = self::DEFAULT_CONTEXT_LENGTH;
    }

    /**
     * @param array<string, mixed> $summary
     */
    public static function respondWith(array $summary): void
    {
        self::$responses[] = (string) json_encode($summary, JSON_UNESCAPED_UNICODE);
    }

    public function complete(array $messages, array $options = []): string
    {
        self::$calls[] = array_values($messages);
        if (self::$failure !== null) {
            throw new RuntimeException(self::$failure);
        }

        return array_shift(self::$responses) ?? (string) json_encode(['summary' => sprintf('Summary %d.', count(self::$calls))]);
    }

    public function completeWithMetadata(array $messages, array $options = []): array
    {
        return ['content' => $this->complete($messages, $options), 'usage' => ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0]];
    }

    public function getModelName(): string
    {
        return 'fake-llm';
    }

    public function getMaxContextLength(): int
    {
        return self::$contextLength;
    }

    public function estimateMessageTokens(array $messages): int
    {
        return 0;
    }
}
