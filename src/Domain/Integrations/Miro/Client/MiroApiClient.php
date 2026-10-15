<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Client;

use App\Domain\Integrations\Miro\DTO\MiroBoard;
use App\Domain\Integrations\Miro\DTO\MiroConnector;
use App\Domain\Integrations\Miro\DTO\MiroItem;
use App\Domain\Integrations\Miro\DTO\MiroPayloadReader;
use App\Domain\Integrations\Miro\DTO\MiroTag;
use Closure;
use Generator;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[Exclude]
class MiroApiClient
{
    public const BASE_URL = 'https://api.miro.com/v2';
    public const PAGE_LIMIT = 50;
    private const MAX_RATE_LIMIT_RETRIES = 3;
    private const MAX_RATE_LIMIT_WAIT_SECONDS = 60;
    private const MILLISECOND_TIMESTAMP_THRESHOLD = 100_000_000_000;
    private const EPOCH_TIMESTAMP_THRESHOLD = 1_000_000_000;

    private readonly Closure $sleeper;

    private readonly Closure $clock;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $accessToken,
        ?Closure $sleeper = null,
        ?Closure $clock = null,
        private readonly int $maxRateLimitRetries = self::MAX_RATE_LIMIT_RETRIES,
    ) {
        $this->sleeper = $sleeper ?? static function (int $seconds): void {
            sleep($seconds);
        };
        $this->clock = $clock ?? static fn (): int => time();
    }

    /**
     * @return Generator<int, MiroBoard>
     */
    public function iterateBoards(?string $teamId = null): Generator
    {
        $query = array_filter(['team_id' => $teamId], static fn (?string $value): bool => $value !== null);

        foreach ($this->paginate('/boards', $query) as $boardPayload) {
            yield MiroBoard::fromApiPayload($boardPayload);
        }
    }

    public function getBoard(string $boardId): MiroBoard
    {
        return MiroBoard::fromApiPayload($this->request('/boards/' . rawurlencode($boardId)));
    }

    /**
     * @return Generator<int, MiroItem>
     */
    public function iterateItems(string $boardId, ?string $type = null, ?string $parentItemId = null): Generator
    {
        $query = array_filter(
            ['type' => $type, 'parent_item_id' => $parentItemId],
            static fn (?string $value): bool => $value !== null,
        );

        foreach ($this->paginate($this->boardPath($boardId, '/items'), $query) as $itemPayload) {
            yield MiroItem::fromApiPayload($itemPayload);
        }
    }

    /**
     * @return Generator<int, MiroConnector>
     */
    public function iterateConnectors(string $boardId): Generator
    {
        foreach ($this->paginate($this->boardPath($boardId, '/connectors')) as $connectorPayload) {
            yield MiroConnector::fromApiPayload($connectorPayload);
        }
    }

    /**
     * @return Generator<int, MiroTag>
     */
    public function iterateTags(string $boardId): Generator
    {
        foreach ($this->paginate($this->boardPath($boardId, '/tags')) as $tagPayload) {
            yield MiroTag::fromApiPayload($tagPayload);
        }
    }

    /**
     * @return list<MiroTag>
     */
    public function getItemTags(string $boardId, string $itemId): array
    {
        $payload = $this->request($this->boardPath($boardId, '/items/' . rawurlencode($itemId) . '/tags'));
        $tagPayloads = MiroPayloadReader::listOfMaps($payload, 'tags');
        if ($tagPayloads === []) {
            $tagPayloads = MiroPayloadReader::listOfMaps($payload, 'data');
        }

        return array_map(MiroTag::fromApiPayload(...), $tagPayloads);
    }

    /**
     * @param array<string, string> $query
     * @return Generator<int, array<mixed>>
     */
    private function paginate(string $path, array $query = []): Generator
    {
        $pageQuery = ['limit' => (string) self::PAGE_LIMIT, ...$query];

        while (true) {
            $page = $this->request($path, $pageQuery);
            $pageItems = MiroPayloadReader::listOfMaps($page, 'data');

            yield from $pageItems;

            $cursor = MiroPayloadReader::string($page, 'cursor');
            if ($cursor !== null && $cursor !== '') {
                if (($pageQuery['cursor'] ?? null) === $cursor) {
                    return;
                }
                $pageQuery['cursor'] = $cursor;
                continue;
            }

            $total = MiroPayloadReader::float($page, 'total');
            if ($total === null || $pageItems === [] || array_key_exists('cursor', $pageQuery)) {
                return;
            }

            $nextOffset = (int) ($pageQuery['offset'] ?? 0) + count($pageItems);
            if ($nextOffset >= (int) $total) {
                return;
            }
            $pageQuery['offset'] = (string) $nextOffset;
        }
    }

    /**
     * @param array<string, string> $query
     * @return array<mixed>
     */
    private function request(string $path, array $query = []): array
    {
        $attempt = 0;

        while (true) {
            try {
                $response = $this->httpClient->request('GET', self::BASE_URL . $path, [
                    'auth_bearer' => $this->accessToken,
                    'headers' => ['Accept' => 'application/json'],
                    'query' => $query,
                ]);
                $statusCode = $response->getStatusCode();

                if ($statusCode === 429) {
                    $secondsToWait = $this->secondsUntilRateLimitReset($response, $attempt);
                    if ($attempt >= $this->maxRateLimitRetries) {
                        throw new MiroRateLimitExceededException(sprintf('Miro rate limit still exceeded for %s after retries.', $path), $secondsToWait);
                    }

                    ($this->sleeper)($secondsToWait);
                    $attempt++;
                    continue;
                }

                return $this->decode($response, $statusCode, $path);
            } catch (ExceptionInterface $exception) {
                throw new MiroApiException(sprintf('Miro request to %s failed: %s', $path, $exception->getMessage()), null, $exception);
            }
        }
    }

    /**
     * @return array<mixed>
     */
    private function decode(ResponseInterface $response, int $statusCode, string $path): array
    {
        if ($statusCode === 401) {
            throw new MiroAuthorizationException(sprintf('Miro rejected the access token for %s.', $path), $statusCode);
        }

        if ($statusCode >= 400) {
            $errorPayload = $response->toArray(false);
            $errorMessage = MiroPayloadReader::string($errorPayload, 'message') ?? 'unknown error';

            throw new MiroApiException(sprintf('Miro request to %s failed with HTTP %d: %s', $path, $statusCode, $errorMessage), $statusCode);
        }

        return $response->toArray(false);
    }

    private function secondsUntilRateLimitReset(ResponseInterface $response, int $attempt): int
    {
        $fallbackSeconds = 2 ** $attempt;
        $resetHeader = $response->getHeaders(false)['x-ratelimit-reset'][0] ?? null;
        if ($resetHeader === null || !is_numeric($resetHeader)) {
            return $fallbackSeconds;
        }

        $resetValue = (int) $resetHeader;
        if ($resetValue >= self::MILLISECOND_TIMESTAMP_THRESHOLD) {
            $resetValue = intdiv($resetValue, 1000);
        }

        $secondsToWait = $resetValue >= self::EPOCH_TIMESTAMP_THRESHOLD
            ? $resetValue - ($this->clock)()
            : $resetValue;

        return max(1, min(self::MAX_RATE_LIMIT_WAIT_SECONDS, $secondsToWait));
    }

    private function boardPath(string $boardId, string $suffix): string
    {
        return '/boards/' . rawurlencode($boardId) . $suffix;
    }
}
