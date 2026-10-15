<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Discord\Client;

use App\Domain\Integrations\Discord\Client\DiscordClient;
use App\Domain\Integrations\Discord\Client\DiscordSleeper;
use App\Domain\Integrations\Discord\Client\Dto\DiscordChannelType;
use App\Domain\Integrations\Discord\Client\Dto\DiscordMessage;
use App\Domain\Integrations\Discord\Client\Dto\DiscordThread;
use App\Domain\Integrations\Discord\Client\Exception\DiscordAccessLostException;
use App\Domain\Integrations\Discord\Client\Exception\DiscordApiException;
use App\Domain\Integrations\Discord\Client\Exception\DiscordRateLimitedException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class DiscordClientTest extends TestCase
{
    private const BASE_URI = 'https://discord.com/api/v10/';

    /**
     * @var list<float>
     */
    private array $sleptSeconds = [];

    /**
     * @var list<array{method: string, url: string}>
     */
    private array $sentRequests = [];

    protected function setUp(): void
    {
        $this->sleptSeconds = [];
        $this->sentRequests = [];
    }

    public function testGetGuildParsesGuild(): void
    {
        $discordClient = $this->createDiscordClient([
            $this->jsonResponse(['id' => '100', 'name' => 'Larp Guild', 'icon' => null, 'owner_id' => '7']),
        ]);

        $guild = $discordClient->getGuild('100');

        self::assertSame('100', $guild->id);
        self::assertSame('Larp Guild', $guild->name);
        self::assertNull($guild->icon);
        self::assertSame('7', $guild->ownerId);
        self::assertSame(['GET', self::BASE_URI . 'guilds/100'], [$this->sentRequests[0]['method'], $this->sentRequests[0]['url']]);
    }

    public function testListGuildChannelsParsesForumTags(): void
    {
        $discordClient = $this->createDiscordClient([
            $this->jsonResponse([
                ['id' => '200', 'type' => 0, 'guild_id' => '100', 'name' => 'general', 'position' => 0],
                [
                    'id' => '201',
                    'type' => 15,
                    'guild_id' => '100',
                    'name' => 'plots',
                    'parent_id' => '199',
                    'position' => 1,
                    'available_tags' => [
                        ['id' => '900', 'name' => 'Quest', 'moderated' => false, 'emoji_id' => null, 'emoji_name' => '⚔️'],
                        ['id' => '901', 'name' => 'Approved', 'moderated' => true, 'emoji_id' => '555', 'emoji_name' => null],
                    ],
                ],
            ]),
        ]);

        $channels = $discordClient->listGuildChannels('100');

        self::assertCount(2, $channels);
        self::assertFalse($channels[0]->isForum());
        self::assertSame([], $channels[0]->availableTags);

        $forum = $channels[1];
        self::assertTrue($forum->isForum());
        self::assertSame(DiscordChannelType::GUILD_FORUM, $forum->getChannelType());
        self::assertSame('199', $forum->parentId);
        self::assertCount(2, $forum->availableTags);
        self::assertSame('900', $forum->availableTags[0]->id);
        self::assertSame('Quest', $forum->availableTags[0]->name);
        self::assertFalse($forum->availableTags[0]->moderated);
        self::assertNull($forum->availableTags[0]->emojiId);
        self::assertSame('⚔️', $forum->availableTags[0]->emojiName);
        self::assertTrue($forum->availableTags[1]->moderated);
        self::assertSame('555', $forum->availableTags[1]->emojiId);
    }

    public function testListActiveThreadsParsesThreadMetadata(): void
    {
        $discordClient = $this->createDiscordClient([
            $this->jsonResponse([
                'threads' => [
                    $this->threadPayload('300', '2026-10-01T10:00:00.000000+00:00', appliedTags: ['900', '901'], lastMessageId: '5000', messageCount: 12),
                ],
                'members' => [],
            ]),
        ]);

        $threads = $discordClient->listActiveThreads('100');

        self::assertCount(1, $threads);
        $thread = $threads[0];
        self::assertSame('300', $thread->id);
        self::assertSame('201', $thread->parentId);
        self::assertSame(['900', '901'], $thread->appliedTags);
        self::assertFalse($thread->archived);
        self::assertTrue($thread->locked);
        self::assertSame('5000', $thread->lastMessageId);
        self::assertSame(12, $thread->messageCount);
        self::assertSame(self::BASE_URI . 'guilds/100/threads/active', $this->sentRequests[0]['url']);
    }

    public function testListArchivedPublicThreadsPaginatesWithBeforeWhileHasMore(): void
    {
        $discordClient = $this->createDiscordClient([
            $this->jsonResponse([
                'threads' => [
                    $this->threadPayload('310', '2026-10-05T12:00:00.250000+00:00', archived: true),
                    $this->threadPayload('309', '2026-10-04T08:30:00.125000+00:00', archived: true),
                ],
                'members' => [],
                'has_more' => true,
            ]),
            $this->jsonResponse([
                'threads' => [
                    $this->threadPayload('305', '2026-09-01T00:00:00.000000+00:00', archived: true),
                ],
                'members' => [],
                'has_more' => false,
            ]),
        ]);

        $threadIds = array_map(
            static fn (DiscordThread $thread): string => $thread->id,
            iterator_to_array($discordClient->listArchivedPublicThreads('201', limit: 2), false),
        );

        self::assertSame(['310', '309', '305'], $threadIds);
        self::assertCount(2, $this->sentRequests);
        self::assertSame(['limit' => '2'], $this->queryOf(0));
        self::assertSame(['limit' => '2', 'before' => '2026-10-04T08:30:00.125+00:00'], $this->queryOf(1));
        self::assertStringStartsWith(self::BASE_URI . 'channels/201/threads/archived/public', $this->sentRequests[1]['url']);
    }

    public function testListMessagesPaginatesWithAfterAcrossTwoPages(): void
    {
        $discordClient = $this->createDiscordClient([
            $this->jsonResponse([
                $this->messagePayload('1002', 'third'),
                $this->messagePayload('999', 'first'),
                $this->messagePayload('1001', 'second'),
            ]),
            $this->jsonResponse([
                $this->messagePayload('1003', 'fourth'),
            ]),
        ]);

        $messages = iterator_to_array($discordClient->listMessages('300', limit: 3), false);

        self::assertSame(
            ['first', 'second', 'third', 'fourth'],
            array_map(static fn (DiscordMessage $message): string => $message->content, $messages),
        );
        self::assertSame(['after' => '0', 'limit' => '3'], $this->queryOf(0));
        self::assertSame(['after' => '1002', 'limit' => '3'], $this->queryOf(1));
        self::assertCount(2, $this->sentRequests);

        $firstMessage = $messages[0];
        self::assertSame('300', $firstMessage->channelId);
        self::assertSame('42', $firstMessage->author->id);
        self::assertSame('Game Master', $firstMessage->author->getDisplayName());
        self::assertSame('2026-10-10T18:00:00+00:00', $firstMessage->timestamp->format(DATE_ATOM));
        self::assertNull($firstMessage->editedTimestamp);
    }

    public function testListMessagesStartsAfterGivenCursor(): void
    {
        $discordClient = $this->createDiscordClient([$this->jsonResponse([])]);

        self::assertSame([], iterator_to_array($discordClient->listMessages('300', '5000', 50), false));
        self::assertSame(['after' => '5000', 'limit' => '50'], $this->queryOf(0));
    }

    public function testRateLimitedRequestIsRetriedAfterRetryAfter(): void
    {
        $discordClient = $this->createDiscordClient([
            new MockResponse(
                json_encode(['message' => 'You are being rate limited.', 'retry_after' => 1.5, 'global' => false], JSON_THROW_ON_ERROR),
                ['http_code' => 429, 'response_headers' => ['Retry-After' => '2', 'X-RateLimit-Scope' => 'user']],
            ),
            $this->jsonResponse(['id' => '100', 'name' => 'Larp Guild']),
        ]);

        $guild = $discordClient->getGuild('100');

        self::assertSame('Larp Guild', $guild->name);
        self::assertCount(2, $this->sentRequests);
        self::assertSame([1.5], $this->sleptSeconds);
    }

    public function testRateLimitedRequestFallsBackToRetryAfterHeader(): void
    {
        $discordClient = $this->createDiscordClient([
            new MockResponse('', ['http_code' => 429, 'response_headers' => ['Retry-After' => '3']]),
            $this->jsonResponse(['id' => '100', 'name' => 'Larp Guild']),
        ]);

        $discordClient->getGuild('100');

        self::assertSame([3.0], $this->sleptSeconds);
    }

    public function testRateLimitRetriesAreBounded(): void
    {
        $rateLimitedResponses = array_map(
            fn (): MockResponse => new MockResponse(
                json_encode(['retry_after' => 0.1, 'global' => false], JSON_THROW_ON_ERROR),
                ['http_code' => 429],
            ),
            range(1, DiscordClient::MAX_RATE_LIMIT_ATTEMPTS),
        );
        $discordClient = $this->createDiscordClient($rateLimitedResponses);

        try {
            $discordClient->getGuild('100');
            self::fail('Expected the client to give up after repeated rate limits.');
        } catch (DiscordRateLimitedException) {
            self::assertCount(DiscordClient::MAX_RATE_LIMIT_ATTEMPTS, $this->sentRequests);
        }
    }

    public function testSingleRateLimitAttemptFailsFastWithRetryAfter(): void
    {
        $discordClient = $this->createDiscordClient([
            new MockResponse(
                json_encode(['retry_after' => 2.5, 'global' => false], JSON_THROW_ON_ERROR),
                ['http_code' => 429],
            ),
        ])->withRateLimitAttempts(1);

        try {
            $discordClient->getGuild('100');
            self::fail('Expected the client to give up after the first rate limit.');
        } catch (DiscordRateLimitedException $rateLimited) {
            self::assertSame(2.5, $rateLimited->getRetryAfterSeconds());
            self::assertCount(1, $this->sentRequests);
            self::assertSame([], $this->sleptSeconds);
        }
    }

    public function testListLatestMessagesReturnsNewestMessagesOldestFirst(): void
    {
        $discordClient = $this->createDiscordClient([
            $this->jsonResponse([
                $this->messagePayload('5003', 'Third'),
                $this->messagePayload('5002', 'Second'),
            ]),
        ]);

        $messages = $discordClient->listLatestMessages('300', 2);

        self::assertSame(['5002', '5003'], array_map(static fn (DiscordMessage $message): string => $message->id, $messages));
        self::assertSame(['limit' => '2'], $this->queryOf(0));
    }

    public function testExhaustedBucketWaitsForReset(): void
    {
        $discordClient = $this->createDiscordClient([
            $this->jsonResponse(['id' => '100', 'name' => 'Larp Guild'], [
                'X-RateLimit-Limit' => '5',
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset-After' => '0.75',
            ]),
        ]);

        $discordClient->getGuild('100');

        self::assertSame([0.75], $this->sleptSeconds);
    }

    public function testForbiddenRaisesAccessLostWithoutRetry(): void
    {
        $discordClient = $this->createDiscordClient([
            new MockResponse('{"message": "Missing Access", "code": 50001}', ['http_code' => 403]),
            $this->jsonResponse(['id' => '100', 'name' => 'Larp Guild']),
        ]);

        try {
            $discordClient->getGuild('100');
            self::fail('Expected DiscordAccessLostException.');
        } catch (DiscordAccessLostException $exception) {
            self::assertSame(403, $exception->getCode());
        }

        self::assertCount(1, $this->sentRequests);
        self::assertSame([], $this->sleptSeconds);
    }

    public function testUnauthorizedRaisesAccessLostWithoutRetry(): void
    {
        $discordClient = $this->createDiscordClient([
            new MockResponse('{"message": "401: Unauthorized", "code": 0}', ['http_code' => 401]),
            $this->jsonResponse([]),
        ]);

        $this->expectException(DiscordAccessLostException::class);

        try {
            iterator_to_array($discordClient->listMessages('300'));
        } finally {
            self::assertCount(1, $this->sentRequests);
        }
    }

    public function testRejectsNonSnowflakeIdentifiers(): void
    {
        $discordClient = $this->createDiscordClient([]);

        $this->expectException(InvalidArgumentException::class);

        $discordClient->getGuild('../users/@me');
    }

    public function testRejectsPageSizeAboveDiscordMaximum(): void
    {
        $discordClient = $this->createDiscordClient([]);

        $this->expectException(InvalidArgumentException::class);

        iterator_to_array($discordClient->listMessages('300', limit: 101));
    }

    public function testExchangeBotAuthorizationCodeUsesClientCredentialsInsteadOfBotToken(): void
    {
        $sentOptions = [];
        $httpClient = (new MockHttpClient(
            function (string $method, string $url, array $options) use (&$sentOptions): MockResponse {
                $sentOptions = ['method' => $method, 'url' => $url, 'headers' => $options['normalized_headers'], 'body' => $options['body']];

                return $this->jsonResponse(['access_token' => 'user-token', 'scope' => 'bot', 'guild' => ['id' => '100', 'name' => 'Larp Guild']]);
            },
            self::BASE_URI,
        ))->withOptions(['headers' => ['Authorization' => 'Bot bot-token']]);
        $discordClient = new DiscordClient($httpClient, $this->createMock(DiscordSleeper::class));

        $authorization = $discordClient->exchangeBotAuthorizationCode('the-code', 'https://larpilot.test/callback', 'client-id', 'client-secret');

        self::assertSame('100', $authorization->guildId);
        self::assertSame('user-token', $authorization->accessToken);
        self::assertSame('POST', $sentOptions['method']);
        self::assertSame(self::BASE_URI . 'oauth2/token', $sentOptions['url']);
        self::assertSame(['Authorization: Basic ' . base64_encode('client-id:client-secret')], $sentOptions['headers']['authorization']);
        parse_str((string) $sentOptions['body'], $body);
        self::assertSame(['grant_type' => 'authorization_code', 'code' => 'the-code', 'redirect_uri' => 'https://larpilot.test/callback'], $body);
    }

    public function testExchangeWithoutGuildReportsNoGuild(): void
    {
        $discordClient = $this->createDiscordClient([
            $this->jsonResponse(['access_token' => 'user-token', 'scope' => 'bot']),
        ]);

        self::assertNull($discordClient->exchangeBotAuthorizationCode('the-code', 'https://larpilot.test/callback', 'client-id', 'client-secret')->guildId);
    }

    public function testRejectedAuthorizationCodeRaisesApiException(): void
    {
        $discordClient = $this->createDiscordClient([
            new MockResponse('{"error":"invalid_grant"}', ['http_code' => 400]),
        ]);

        $this->expectException(DiscordApiException::class);

        $discordClient->exchangeBotAuthorizationCode('stale-code', 'https://larpilot.test/callback', 'client-id', 'client-secret');
    }

    public function testLeaveGuildSendsDelete(): void
    {
        $discordClient = $this->createDiscordClient([
            new MockResponse('', ['http_code' => 204]),
        ]);

        $discordClient->leaveGuild('100');

        self::assertSame(['DELETE', self::BASE_URI . 'users/@me/guilds/100'], [$this->sentRequests[0]['method'], $this->sentRequests[0]['url']]);
    }

    public function testLeavingAnUnknownGuildIsNotAnError(): void
    {
        $discordClient = $this->createDiscordClient([
            new MockResponse('{"message":"Unknown Guild","code":10004}', ['http_code' => 404]),
        ]);

        $discordClient->leaveGuild('100');

        self::assertCount(1, $this->sentRequests);
    }

    /**
     * @param list<MockResponse> $responses
     */
    private function createDiscordClient(array $responses): DiscordClient
    {
        $remainingResponses = $responses;
        $httpClient = new MockHttpClient(
            function (string $method, string $url) use (&$remainingResponses): MockResponse {
                $this->sentRequests[] = ['method' => $method, 'url' => $url];
                $response = array_shift($remainingResponses);
                if ($response === null) {
                    self::fail(sprintf('Unexpected Discord request: %s %s', $method, $url));
                }

                return $response;
            },
            self::BASE_URI,
        );

        $sleeper = new class($this->sleptSeconds) implements DiscordSleeper {
            /**
             * @param list<float> $sleptSeconds
             */
            public function __construct(private array &$sleptSeconds)
            {
            }

            public function sleep(float $seconds): void
            {
                $this->sleptSeconds[] = $seconds;
            }
        };

        return new DiscordClient($httpClient, $sleeper);
    }

    /**
     * @param array<mixed> $payload
     * @param array<string, string> $headers
     */
    private function jsonResponse(array $payload, array $headers = []): MockResponse
    {
        return new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR), [
            'http_code' => 200,
            'response_headers' => ['Content-Type' => 'application/json'] + $headers,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function queryOf(int $requestIndex): array
    {
        parse_str((string) parse_url($this->sentRequests[$requestIndex]['url'], PHP_URL_QUERY), $query);

        return $query;
    }

    /**
     * @param list<string> $appliedTags
     * @return array<string, mixed>
     */
    private function threadPayload(
        string $threadId,
        string $archiveTimestamp,
        bool $archived = false,
        array $appliedTags = [],
        ?string $lastMessageId = null,
        int $messageCount = 0,
    ): array {
        return [
            'id' => $threadId,
            'type' => 11,
            'guild_id' => '100',
            'parent_id' => '201',
            'name' => 'Thread ' . $threadId,
            'applied_tags' => $appliedTags,
            'last_message_id' => $lastMessageId,
            'message_count' => $messageCount,
            'thread_metadata' => [
                'archived' => $archived,
                'locked' => !$archived,
                'archive_timestamp' => $archiveTimestamp,
                'auto_archive_duration' => 1440,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function messagePayload(string $messageId, string $content): array
    {
        return [
            'id' => $messageId,
            'channel_id' => '300',
            'author' => ['id' => '42', 'username' => 'gm', 'global_name' => 'Game Master', 'bot' => false],
            'content' => $content,
            'timestamp' => '2026-10-10T18:00:00.000000+00:00',
            'edited_timestamp' => null,
        ];
    }
}
