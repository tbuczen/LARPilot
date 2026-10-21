<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client;

use App\Domain\Integrations\Discord\Client\Dto\DiscordBotAuthorization;
use App\Domain\Integrations\Discord\Client\Dto\DiscordChannel;
use App\Domain\Integrations\Discord\Client\Dto\DiscordChannelType;
use App\Domain\Integrations\Discord\Client\Dto\DiscordGuild;
use App\Domain\Integrations\Discord\Client\Dto\DiscordGuildMember;
use App\Domain\Integrations\Discord\Client\Dto\DiscordMessage;
use App\Domain\Integrations\Discord\Client\Dto\DiscordThread;
use App\Domain\Integrations\Discord\Client\Exception\DiscordAccessLostException;
use App\Domain\Integrations\Discord\Client\Exception\DiscordApiException;
use App\Domain\Integrations\Discord\Client\Exception\DiscordRateLimitedException;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use InvalidArgumentException;
use JsonException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class DiscordClient
{
    public const MAX_PAGE_SIZE = 100;

    public const MAX_RATE_LIMIT_ATTEMPTS = 3;

    public const MAX_MEMBER_SEARCH_RESULTS = 1000;

    public const MAX_MESSAGE_LENGTH = 2000;

    public const MAX_THREAD_NAME_LENGTH = 100;

    public const THREAD_AUTO_ARCHIVE_MINUTES = 10080;

    private const NO_MENTIONS = ['parse' => []];

    private const DEFAULT_RETRY_AFTER_SECONDS = 1.0;

    private int $rateLimitAttempts = self::MAX_RATE_LIMIT_ATTEMPTS;

    public function __construct(
        private readonly HttpClientInterface $discordClient,
        private readonly DiscordSleeper $sleeper,
    ) {
    }

    public function withRateLimitAttempts(int $rateLimitAttempts): self
    {
        if ($rateLimitAttempts < 1) {
            throw new InvalidArgumentException(sprintf('Discord rate limit attempts must be at least 1, %d given.', $rateLimitAttempts));
        }

        $client = clone $this;
        $client->rateLimitAttempts = $rateLimitAttempts;

        return $client;
    }

    public function getGuild(string $guildId): DiscordGuild
    {
        $this->assertSnowflake($guildId);

        return DiscordGuild::fromArray($this->get(sprintf('guilds/%s', $guildId)));
    }

    /**
     * @return list<DiscordChannel>
     */
    public function listGuildChannels(string $guildId): array
    {
        $this->assertSnowflake($guildId);

        return array_map(
            DiscordChannel::fromArray(...),
            $this->getList(sprintf('guilds/%s/channels', $guildId)),
        );
    }

    /**
     * @return list<DiscordThread>
     */
    public function listActiveThreads(string $guildId): array
    {
        $this->assertSnowflake($guildId);
        $path = sprintf('guilds/%s/threads/active', $guildId);

        return array_map(DiscordThread::fromArray(...), $this->extractList($path, $this->get($path), 'threads'));
    }

    /**
     * @return Generator<int, DiscordThread>
     */
    public function listArchivedPublicThreads(
        string $channelId,
        ?DateTimeImmutable $before = null,
        int $limit = self::MAX_PAGE_SIZE,
    ): Generator {
        $this->assertSnowflake($channelId);
        $this->assertPageSize($limit);
        $path = sprintf('channels/%s/threads/archived/public', $channelId);

        do {
            $query = ['limit' => $limit];
            if ($before !== null) {
                $query['before'] = $before->format(DateTimeInterface::RFC3339_EXTENDED);
            }

            $page = $this->get($path, $query);
            $threads = array_map(DiscordThread::fromArray(...), $this->extractList($path, $page, 'threads'));

            foreach ($threads as $thread) {
                yield $thread;
            }

            $oldestArchiveTimestamp = $this->findOldestArchiveTimestamp($threads);
            if ($oldestArchiveTimestamp === null) {
                return;
            }

            $before = $oldestArchiveTimestamp;
        } while (($page['has_more'] ?? false) === true);
    }

    /**
     * @return Generator<int, DiscordMessage>
     */
    public function listMessages(string $channelOrThreadId, ?string $after = null, int $limit = self::MAX_PAGE_SIZE): Generator
    {
        $this->assertSnowflake($channelOrThreadId);
        $this->assertPageSize($limit);
        $cursor = $after ?? '0';
        $this->assertSnowflake($cursor);
        $path = sprintf('channels/%s/messages', $channelOrThreadId);

        while (true) {
            $messages = array_map(
                DiscordMessage::fromArray(...),
                $this->getList($path, ['after' => $cursor, 'limit' => $limit]),
            );
            usort($messages, fn (DiscordMessage $left, DiscordMessage $right): int => $this->compareSnowflakes($left->id, $right->id));

            foreach ($messages as $message) {
                yield $message;
            }

            if (count($messages) < $limit) {
                return;
            }

            $cursor = $messages[array_key_last($messages)]->id;
        }
    }

    /**
     * @return list<DiscordMessage>
     */
    public function listLatestMessages(string $channelOrThreadId, int $limit = self::MAX_PAGE_SIZE): array
    {
        $this->assertSnowflake($channelOrThreadId);
        $this->assertPageSize($limit);

        $messages = array_map(
            DiscordMessage::fromArray(...),
            $this->getList(sprintf('channels/%s/messages', $channelOrThreadId), ['limit' => $limit]),
        );
        usort($messages, fn (DiscordMessage $left, DiscordMessage $right): int => $this->compareSnowflakes($left->id, $right->id));

        return array_slice($messages, -$limit);
    }

    /**
     * @return list<DiscordGuildMember>
     */
    public function searchGuildMembers(string $guildId, string $query, int $limit = 10): array
    {
        $this->assertSnowflake($guildId);
        if ($limit < 1 || $limit > self::MAX_MEMBER_SEARCH_RESULTS) {
            throw new InvalidArgumentException(sprintf('Discord member search limit must be between 1 and %d, %d given.', self::MAX_MEMBER_SEARCH_RESULTS, $limit));
        }

        return array_map(
            DiscordGuildMember::fromArray(...),
            $this->getList(sprintf('guilds/%s/members/search', $guildId), ['query' => $query, 'limit' => $limit]),
        );
    }

    public function startPublicThread(string $channelId, string $name): DiscordThread
    {
        $this->assertSnowflake($channelId);
        $path = sprintf('channels/%s/threads', $channelId);

        return DiscordThread::fromArray($this->decode($path, $this->send('POST', $path, ['json' => [
            'name' => self::threadName($name),
            'type' => DiscordChannelType::PUBLIC_THREAD->value,
            'auto_archive_duration' => self::THREAD_AUTO_ARCHIVE_MINUTES,
        ]])));
    }

    public function startForumPost(string $forumChannelId, string $name, string $content): DiscordThread
    {
        $this->assertSnowflake($forumChannelId);
        $path = sprintf('channels/%s/threads', $forumChannelId);

        return DiscordThread::fromArray($this->decode($path, $this->send('POST', $path, ['json' => [
            'name' => self::threadName($name),
            'auto_archive_duration' => self::THREAD_AUTO_ARCHIVE_MINUTES,
            'message' => ['content' => $this->messageContent($content), 'allowed_mentions' => self::NO_MENTIONS],
        ]])));
    }

    public function postMessage(string $channelOrThreadId, string $content): DiscordMessage
    {
        $this->assertSnowflake($channelOrThreadId);
        $path = sprintf('channels/%s/messages', $channelOrThreadId);

        return DiscordMessage::fromArray($this->decode($path, $this->send('POST', $path, ['json' => [
            'content' => $this->messageContent($content),
            'allowed_mentions' => self::NO_MENTIONS,
        ]])));
    }

    public function exchangeBotAuthorizationCode(string $code, string $redirectUri, string $clientId, string $clientSecret): DiscordBotAuthorization
    {
        $path = 'oauth2/token';
        $response = $this->send('POST', $path, [
            'headers' => ['Authorization' => $this->basicAuthorization($clientId, $clientSecret)],
            'body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ],
        ]);

        return DiscordBotAuthorization::fromArray($this->decode($path, $response));
    }

    public function revokeAccessToken(string $accessToken, string $clientId, string $clientSecret): void
    {
        $this->send('POST', 'oauth2/token/revoke', [
            'headers' => ['Authorization' => $this->basicAuthorization($clientId, $clientSecret)],
            'body' => [
                'token' => $accessToken,
                'token_type_hint' => 'access_token',
            ],
        ]);
    }

    public function leaveGuild(string $guildId): void
    {
        $this->assertSnowflake($guildId);

        try {
            $this->send('DELETE', sprintf('users/@me/guilds/%s', $guildId));
        } catch (DiscordApiException $exception) {
            if ($exception->getCode() === 404) {
                return;
            }

            throw $exception;
        }
    }

    /**
     * @param array<string, scalar> $query
     * @return list<array<string, mixed>>
     */
    private function getList(string $path, array $query = []): array
    {
        $payload = $this->get($path, $query);
        if (!array_is_list($payload)) {
            throw DiscordApiException::invalidPayload($path);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<array<string, mixed>>
     */
    private function extractList(string $path, array $payload, string $key): array
    {
        $items = $payload[$key] ?? null;
        if (!is_array($items) || !array_is_list($items)) {
            throw DiscordApiException::invalidPayload($path);
        }

        return $items;
    }

    /**
     * @param array<string, scalar> $query
     * @return array<mixed>
     */
    private function get(string $path, array $query = []): array
    {
        return $this->decode($path, $this->send('GET', $path, ['query' => $query]));
    }

    /**
     * @return array<mixed>
     */
    private function decode(string $path, ResponseInterface $response): array
    {
        try {
            return $response->toArray();
        } catch (ExceptionInterface) {
            throw DiscordApiException::invalidPayload($path);
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function send(string $method, string $path, array $options = []): ResponseInterface
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                $response = $this->discordClient->request($method, $path, $options);
                $statusCode = $response->getStatusCode();
            } catch (ExceptionInterface $exception) {
                throw new DiscordApiException(sprintf('Discord API request to "%s" failed: %s', $path, $exception->getMessage()), 0, $exception);
            }

            if ($statusCode === 401 || $statusCode === 403) {
                throw DiscordAccessLostException::forStatus($path, $statusCode);
            }

            if ($statusCode === 429) {
                $retryAfterSeconds = $this->resolveRetryAfter($response);
                if ($attempt >= $this->rateLimitAttempts) {
                    throw DiscordRateLimitedException::retriesExhausted($path, $attempt, $retryAfterSeconds);
                }

                $this->sleeper->sleep($retryAfterSeconds);
                continue;
            }

            if ($statusCode >= 300) {
                throw DiscordApiException::unexpectedStatus($path, $statusCode);
            }

            $this->waitForExhaustedBucket($response);

            return $response;
        }
    }

    private static function threadName(string $name): string
    {
        $name = trim($name);

        return mb_substr($name === '' ? '-' : $name, 0, self::MAX_THREAD_NAME_LENGTH);
    }

    private function messageContent(string $content): string
    {
        if (trim($content) === '' || mb_strlen($content) > self::MAX_MESSAGE_LENGTH) {
            throw new InvalidArgumentException(sprintf('Discord messages must have 1 to %d characters.', self::MAX_MESSAGE_LENGTH));
        }

        return $content;
    }

    private function basicAuthorization(string $clientId, string $clientSecret): string
    {
        return 'Basic ' . base64_encode(rawurlencode($clientId) . ':' . rawurlencode($clientSecret));
    }

    private function resolveRetryAfter(ResponseInterface $response): float
    {
        try {
            $body = json_decode($response->getContent(false), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException | ExceptionInterface) {
            $body = null;
        }

        if (is_array($body) && is_numeric($body['retry_after'] ?? null)) {
            return max(0.0, (float) $body['retry_after']);
        }

        $retryAfterHeader = $this->readHeader($response, 'retry-after');
        if (is_numeric($retryAfterHeader)) {
            return max(0.0, (float) $retryAfterHeader);
        }

        return self::DEFAULT_RETRY_AFTER_SECONDS;
    }

    private function waitForExhaustedBucket(ResponseInterface $response): void
    {
        if ($this->readHeader($response, 'x-ratelimit-remaining') !== '0') {
            return;
        }

        $resetAfter = $this->readHeader($response, 'x-ratelimit-reset-after');
        if (!is_numeric($resetAfter)) {
            return;
        }

        $this->sleeper->sleep(max(0.0, (float) $resetAfter));
    }

    private function readHeader(ResponseInterface $response, string $name): ?string
    {
        try {
            $headers = $response->getHeaders(false);
        } catch (ExceptionInterface) {
            return null;
        }

        return $headers[$name][0] ?? null;
    }

    /**
     * @param list<DiscordThread> $threads
     */
    private function findOldestArchiveTimestamp(array $threads): ?DateTimeImmutable
    {
        $oldestArchiveTimestamp = null;
        foreach ($threads as $thread) {
            if ($thread->archiveTimestamp === null) {
                continue;
            }

            if ($oldestArchiveTimestamp === null || $thread->archiveTimestamp < $oldestArchiveTimestamp) {
                $oldestArchiveTimestamp = $thread->archiveTimestamp;
            }
        }

        return $oldestArchiveTimestamp;
    }

    private function compareSnowflakes(string $left, string $right): int
    {
        return [strlen($left), $left] <=> [strlen($right), $right];
    }

    private function assertSnowflake(string $snowflake): void
    {
        if (ctype_digit($snowflake)) {
            return;
        }

        throw new InvalidArgumentException(sprintf('"%s" is not a valid Discord snowflake.', $snowflake));
    }

    private function assertPageSize(int $limit): void
    {
        if ($limit >= 1 && $limit <= self::MAX_PAGE_SIZE) {
            return;
        }

        throw new InvalidArgumentException(sprintf('Discord page size must be between 1 and %d, %d given.', self::MAX_PAGE_SIZE, $limit));
    }
}
