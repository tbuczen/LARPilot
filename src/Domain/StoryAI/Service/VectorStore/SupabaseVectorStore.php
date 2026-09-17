<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\VectorStore;

use App\Domain\StoryAI\DTO\VectorDocument;
use App\Domain\StoryAI\DTO\VectorSearchResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Vector store implementation using Supabase with pgvector.
 *
 * Requires:
 * - Supabase project with pgvector extension enabled
 * - Table 'larpilot_embeddings' created (see docs/VECTOR_STORE_SETUP.md)
 * - RPC function 'search_embeddings' for similarity search
 *
 * Environment variables:
 * - SUPABASE_URL: Project URL (e.g., https://xxx.supabase.co)
 * - SUPABASE_ANON_KEY: Anonymous/public API key
 * - SUPABASE_SERVICE_KEY: Service role key (for write operations)
 */
final readonly class SupabaseVectorStore implements VectorStoreInterface
{
    private const TABLE_NAME = 'larpilot_embeddings';
    private const SEARCH_FUNCTION = 'search_embeddings';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $supabaseUrl,
        private string $supabaseServiceKey,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function upsert(VectorDocument $document): void
    {
        $this->upsertBatch([$document]);
    }

    public function upsertBatch(array $documents): void
    {
        if (empty($documents)) {
            return;
        }

        $rows = array_map(
            fn (VectorDocument $doc) => $this->documentToRow($doc),
            $documents
        );

        $response = $this->request('POST', '/rest/v1/' . self::TABLE_NAME, [
            'headers' => [
                'Prefer' => 'resolution=merge-duplicates',
            ],
            'json' => $rows,
        ]);

        $this->logger?->debug('Upserted documents to Supabase', [
            'count' => count($documents),
            'status' => $response['status'] ?? 'unknown',
        ]);
    }

    public function search(
        array $embedding,
        Uuid $larpId,
        int $limit = 10,
        float $minSimilarity = 0.5,
        array $filters = [],
    ): array {
        // Call the RPC function for vector similarity search
        $response = $this->request('POST', '/rest/v1/rpc/' . self::SEARCH_FUNCTION, [
            'json' => [
                'query_embedding' => $embedding,
                'larp_id_filter' => $larpId->toRfc4122(),
                'match_threshold' => $minSimilarity,
                'match_count' => $limit,
                'type_filter' => $filters['type'] ?? null,
                'entity_type_filter' => $filters['entity_type'] ?? null,
            ],
        ]);

        if (!isset($response['data']) || !is_array($response['data'])) {
            $this->logger?->warning('Empty or invalid search response from Supabase', [
                'response' => $response,
            ]);
            return [];
        }

        return array_map(
            fn (array $row) => $this->rowToSearchResult($row),
            $response['data']
        );
    }

    public function delete(Uuid $entityId): void
    {
        $this->request('DELETE', '/rest/v1/' . self::TABLE_NAME, [
            'query' => [
                'entity_id' => 'eq.' . $entityId->toRfc4122(),
            ],
        ]);

        $this->logger?->debug('Deleted document from Supabase', [
            'entity_id' => $entityId->toRfc4122(),
        ]);
    }

    public function deleteByFilter(array $filter): int
    {
        $query = [];
        foreach ($filter as $key => $value) {
            if ($value instanceof Uuid) {
                $query[$key] = 'eq.' . $value->toRfc4122();
            } else {
                $query[$key] = 'eq.' . $value;
            }
        }

        $response = $this->request('DELETE', '/rest/v1/' . self::TABLE_NAME, [
            'headers' => [
                'Prefer' => 'return=representation',
            ],
            'query' => $query,
        ]);

        $count = is_array($response['data'] ?? null) ? count($response['data']) : 0;

        $this->logger?->debug('Deleted documents by filter from Supabase', [
            'filter' => $filter,
            'count' => $count,
        ]);

        return $count;
    }

    public function exists(Uuid $entityId): bool
    {
        $response = $this->request('GET', '/rest/v1/' . self::TABLE_NAME, [
            'headers' => [
                'Prefer' => 'count=exact',
            ],
            'query' => [
                'entity_id' => 'eq.' . $entityId->toRfc4122(),
                'select' => 'entity_id',
            ],
        ]);

        return ($response['count'] ?? 0) > 0;
    }

    public function findByEntityId(Uuid $entityId): ?VectorDocument
    {
        $response = $this->request('GET', '/rest/v1/' . self::TABLE_NAME, [
            'query' => [
                'entity_id' => 'eq.' . $entityId->toRfc4122(),
                'select' => '*',
            ],
        ]);

        if (empty($response['data']) || !is_array($response['data'])) {
            return null;
        }

        $row = $response['data'][0] ?? null;
        if (!$row) {
            return null;
        }

        return $this->rowToDocument($row);
    }

    public function isAvailable(): bool
    {
        return !empty($this->supabaseUrl) && !empty($this->supabaseServiceKey);
    }

    public function getProviderName(): string
    {
        return 'supabase';
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $options = []): array
    {
        $url = rtrim($this->supabaseUrl, '/') . $path;

        $defaultHeaders = [
            'apikey' => $this->supabaseServiceKey,
            'Authorization' => 'Bearer ' . $this->supabaseServiceKey,
            'Content-Type' => 'application/json',
        ];

        $options['headers'] = array_merge($defaultHeaders, $options['headers'] ?? []);

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            $data = json_decode($content, true);

            // Check for Supabase error response
            if ($statusCode >= 400) {
                $this->logger?->error('Supabase API error', [
                    'status' => $statusCode,
                    'url' => $url,
                    'error' => $data['message'] ?? $content,
                ]);
                throw new \RuntimeException(
                    sprintf('Supabase API error: %s', $data['message'] ?? $content)
                );
            }

            // Parse count header if present
            $countHeader = $response->getHeaders(false)['content-range'][0] ?? null;
            $count = null;
            if ($countHeader && preg_match('/\/(\d+)$/', $countHeader, $matches)) {
                $count = (int) $matches[1];
            }

            return [
                'status' => $statusCode,
                'data' => $data,
                'count' => $count,
            ];
        } catch (\Throwable $e) {
            $this->logger?->error('Supabase request failed', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function documentToRow(VectorDocument $document): array
    {
        return [
            'id' => Uuid::v4()->toRfc4122(),
            'entity_id' => $document->entityId->toRfc4122(),
            'larp_id' => $document->larpId->toRfc4122(),
            'entity_type' => $document->entityType,
            'type' => $document->type,
            'title' => $document->title,
            'serialized_content' => $document->serializedContent,
            'content_hash' => $document->contentHash,
            'embedding' => '[' . implode(',', $document->embedding) . ']',
            'embedding_model' => $document->embeddingModel,
            'metadata' => json_encode($document->metadata),
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowToDocument(array $row): VectorDocument
    {
        $embedding = $row['embedding'];
        if (is_string($embedding)) {
            // Parse pgvector string format: [0.1,0.2,0.3]
            $embedding = json_decode($embedding, true) ?? [];
        }

        return new VectorDocument(
            entityId: Uuid::fromString($row['entity_id']),
            larpId: Uuid::fromString($row['larp_id']),
            entityType: $row['entity_type'],
            type: $row['type'],
            title: $row['title'],
            serializedContent: $row['serialized_content'],
            contentHash: $row['content_hash'],
            embedding: $embedding,
            embeddingModel: $row['embedding_model'] ?? 'text-embedding-3-small',
            metadata: is_string($row['metadata']) ? json_decode($row['metadata'], true) : ($row['metadata'] ?? []),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowToSearchResult(array $row): VectorSearchResult
    {
        return new VectorSearchResult(
            entityId: Uuid::fromString($row['entity_id']),
            larpId: Uuid::fromString($row['larp_id']),
            entityType: $row['entity_type'],
            type: $row['type'],
            title: $row['title'],
            content: $row['serialized_content'],
            similarity: (float) $row['similarity'],
            metadata: is_string($row['metadata']) ? json_decode($row['metadata'], true) : ($row['metadata'] ?? []),
        );
    }
}
