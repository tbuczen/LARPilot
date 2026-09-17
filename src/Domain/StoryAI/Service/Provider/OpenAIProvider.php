<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\Provider;

use App\Domain\StoryAI\DTO\ChatMessage;
use OpenAI\Client;
use OpenAI\Factory;
use Psr\Log\LoggerInterface;

/**
 * OpenAI provider implementation for embeddings and completions.
 */
class OpenAIProvider implements EmbeddingProviderInterface, LLMProviderInterface
{
    private ?Client $client = null;

    // Embedding model constants
    private const EMBEDDING_DIMENSIONS = [
        'text-embedding-3-small' => 1536,
        'text-embedding-3-large' => 3072,
        'text-embedding-ada-002' => 1536,
    ];

    private const EMBEDDING_MAX_TOKENS = [
        'text-embedding-3-small' => 8191,
        'text-embedding-3-large' => 8191,
        'text-embedding-ada-002' => 8191,
    ];

    // Completion model context lengths
    private const COMPLETION_CONTEXT_LENGTH = [
        'gpt-4o-mini' => 128000,
        'gpt-4o' => 128000,
        'gpt-4-turbo' => 128000,
        'gpt-3.5-turbo' => 16385,
    ];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $embeddingModel = 'text-embedding-3-small',
        private readonly string $completionModel = 'gpt-4o-mini',
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    private function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = (new Factory())
                ->withApiKey($this->apiKey)
                ->make();
        }
        return $this->client;
    }

    // ========================================
    // EmbeddingProviderInterface Implementation
    // ========================================

    public function embed(string $text): array
    {
        $this->logger?->debug('Generating embedding', [
            'model' => $this->embeddingModel,
            'text_length' => strlen($text),
        ]);

        $response = $this->getClient()->embeddings()->create([
            'model' => $this->embeddingModel,
            'input' => $text,
        ]);

        return $response->embeddings[0]->embedding;
    }

    public function embedBatch(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        $this->logger?->debug('Generating batch embeddings', [
            'model' => $this->embeddingModel,
            'count' => count($texts),
        ]);

        $response = $this->getClient()->embeddings()->create([
            'model' => $this->embeddingModel,
            'input' => $texts,
        ]);

        $embeddings = [];
        foreach ($response->embeddings as $embedding) {
            $embeddings[] = $embedding->embedding;
        }

        return $embeddings;
    }

    public function getModelName(): string
    {
        return $this->embeddingModel;
    }

    public function getDimensions(): int
    {
        return self::EMBEDDING_DIMENSIONS[$this->embeddingModel] ?? 1536;
    }

    public function estimateTokenCount(string $text): int
    {
        // Estimation: ~4 characters per token for English text
        // This is a heuristic; for precise counts, use tiktoken library
        return (int) ceil(strlen($text) / 4);
    }

    public function getMaxTokens(): int
    {
        return self::EMBEDDING_MAX_TOKENS[$this->embeddingModel] ?? 8191;
    }

    // ========================================
    // LLMProviderInterface Implementation
    // ========================================

    public function complete(array $messages, array $options = []): string
    {
        $result = $this->completeWithMetadata($messages, $options);
        return $result['content'];
    }

    public function completeWithMetadata(array $messages, array $options = []): array
    {
        $this->logger?->debug('Generating completion', [
            'model' => $this->completionModel,
            'message_count' => count($messages),
        ]);

        $payload = [
            'model' => $this->completionModel,
            'messages' => array_map(fn (ChatMessage $m) => $m->toArray(), $messages),
            'temperature' => $options['temperature'] ?? 0.7,
        ];

        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }

        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        $response = $this->getClient()->chat()->create($payload);

        $content = $response->choices[0]->message->content ?? '';
        $usage = [
            'prompt_tokens' => $response->usage->promptTokens,
            'completion_tokens' => $response->usage->completionTokens,
            'total_tokens' => $response->usage->totalTokens,
        ];

        $this->logger?->info('Completion generated', [
            'model' => $this->completionModel,
            'usage' => $usage,
        ]);

        return [
            'content' => $content,
            'usage' => $usage,
        ];
    }

    public function getMaxContextLength(): int
    {
        return self::COMPLETION_CONTEXT_LENGTH[$this->completionModel] ?? 16385;
    }

    public function estimateMessageTokens(array $messages): int
    {
        $totalChars = 0;
        foreach ($messages as $message) {
            // Add overhead for message structure (~4 tokens per message)
            $totalChars += strlen($message->content) + 16;
        }
        return (int) ceil($totalChars / 4);
    }

    /**
     * Get the completion model name.
     */
    public function getCompletionModelName(): string
    {
        return $this->completionModel;
    }
}
