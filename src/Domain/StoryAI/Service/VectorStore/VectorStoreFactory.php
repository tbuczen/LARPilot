<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\VectorStore;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Factory for creating vector store instances based on configuration.
 *
 * Environment variable VECTOR_STORE_DSN determines the provider:
 * - supabase://service-key@project-ref  -> SupabaseVectorStore
 * - null://                              -> NullVectorStore (disabled)
 * - (empty/not set)                      -> NullVectorStore
 */
final readonly class VectorStoreFactory
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function create(string $dsn): VectorStoreInterface
    {
        if (empty($dsn) || $dsn === 'null://') {
            $this->logger?->info('Vector store disabled (DSN not configured)');
            return new NullVectorStore($this->logger);
        }

        $parsed = $this->parseDsn($dsn);

        return match ($parsed['scheme']) {
            'supabase' => $this->createSupabase($parsed),
            'null' => new NullVectorStore($this->logger),
            default => throw new \InvalidArgumentException(
                sprintf('Unknown vector store provider: %s', $parsed['scheme'])
            ),
        };
    }

    /**
     * @param array<string, string|null> $parsed
     */
    private function createSupabase(array $parsed): SupabaseVectorStore
    {
        $serviceKey = $parsed['user'] ?? '';
        $projectRef = $parsed['host'] ?? '';

        if (empty($serviceKey) || empty($projectRef)) {
            throw new \InvalidArgumentException(
                'Supabase DSN must include service key and project reference: supabase://SERVICE_KEY@PROJECT_REF'
            );
        }

        // Support both full URL and just project reference
        $url = str_contains($projectRef, '.')
            ? 'https://' . $projectRef
            : 'https://' . $projectRef . '.supabase.co';

        $this->logger?->info('Creating Supabase vector store', [
            'url' => $url,
        ]);

        return new SupabaseVectorStore(
            httpClient: $this->httpClient,
            supabaseUrl: $url,
            supabaseServiceKey: $serviceKey,
            logger: $this->logger,
        );
    }

    /**
     * @return array<string, string|null>
     */
    private function parseDsn(string $dsn): array
    {
        $parts = parse_url($dsn);

        if ($parts === false) {
            throw new \InvalidArgumentException(
                sprintf('Invalid vector store DSN: %s', $dsn)
            );
        }

        return [
            'scheme' => $parts['scheme'] ?? '',
            'user' => isset($parts['user']) ? urldecode($parts['user']) : null,
            'pass' => isset($parts['pass']) ? urldecode($parts['pass']) : null,
            'host' => $parts['host'] ?? null,
            'port' => isset($parts['port']) ? (string) $parts['port'] : null,
            'path' => $parts['path'] ?? null,
            'query' => $parts['query'] ?? null,
        ];
    }
}
