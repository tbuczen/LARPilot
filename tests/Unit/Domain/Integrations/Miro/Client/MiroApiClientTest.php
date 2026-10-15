<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Miro\Client;

use App\Domain\Integrations\Miro\Client\MiroApiClient;
use App\Domain\Integrations\Miro\Client\MiroApiException;
use App\Domain\Integrations\Miro\Client\MiroAuthorizationException;
use App\Domain\Integrations\Miro\Client\MiroRateLimitExceededException;
use App\Domain\Integrations\Miro\DTO\MiroItem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class MiroApiClientTest extends TestCase
{
    private const NOW = 1_760_000_000;

    /** @var list<int> */
    private array $sleptSeconds = [];

    /** @var list<array{method: string, url: string, headers: list<string>}> */
    private array $sentRequests = [];

    protected function setUp(): void
    {
        $this->sleptSeconds = [];
        $this->sentRequests = [];
    }

    public function testWalksThreePagesOfItemsFollowingCursor(): void
    {
        $miroClient = $this->createMiroClient([
            $this->jsonResponse(['data' => [$this->itemPayload('item-1'), $this->itemPayload('item-2')], 'cursor' => 'cursor-page-2', 'limit' => 50, 'size' => 2]),
            $this->jsonResponse(['data' => [$this->itemPayload('item-3')], 'cursor' => 'cursor-page-3', 'limit' => 50, 'size' => 1]),
            $this->jsonResponse(['data' => [$this->itemPayload('item-4')], 'limit' => 50, 'size' => 1, 'total' => 4]),
        ]);

        $itemIds = array_map(
            static fn (MiroItem $item): string => $item->id,
            iterator_to_array($miroClient->iterateItems('board-1'), false),
        );

        $this->assertSame(['item-1', 'item-2', 'item-3', 'item-4'], $itemIds);
        $this->assertCount(3, $this->sentRequests);
        $this->assertStringStartsWith('https://api.miro.com/v2/boards/board-1/items?', $this->sentRequests[0]['url']);
        $this->assertStringContainsString('limit=50', $this->sentRequests[0]['url']);
        $this->assertStringNotContainsString('cursor=', $this->sentRequests[0]['url']);
        $this->assertStringContainsString('cursor=cursor-page-2', $this->sentRequests[1]['url']);
        $this->assertStringContainsString('cursor=cursor-page-3', $this->sentRequests[2]['url']);
        $this->assertContains('Authorization: Bearer test-token', $this->sentRequests[0]['headers']);
    }

    public function testReadsFrameChildrenViaParentItemId(): void
    {
        $childPayload = $this->itemPayload('sticky-1');
        $childPayload['parent'] = ['id' => 'frame-1', 'links' => ['self' => 'https://api.miro.com/v2/boards/board-1/items/frame-1']];
        $miroClient = $this->createMiroClient([
            $this->jsonResponse(['data' => [$childPayload], 'limit' => 50, 'size' => 1, 'total' => 1]),
        ]);

        $frameChildren = iterator_to_array($miroClient->iterateItems('board-1', 'sticky_note', 'frame-1'), false);

        $this->assertCount(1, $frameChildren);
        $this->assertSame('frame-1', $frameChildren[0]->parentId);
        $this->assertStringContainsString('parent_item_id=frame-1', $this->sentRequests[0]['url']);
        $this->assertStringContainsString('type=sticky_note', $this->sentRequests[0]['url']);
    }

    public function testParsesItemFieldsTolerantly(): void
    {
        $miroClient = $this->createMiroClient([
            $this->jsonResponse(['data' => [[
                'id' => '3458764517517819000',
                'type' => 'sticky_note',
                'data' => ['content' => '<p>Ser Aldric &amp; the <strong>oath</strong></p><p>second line</p>', 'shape' => 'square'],
                'position' => ['x' => 100.5, 'y' => -20, 'origin' => 'center', 'relativeTo' => 'parent_top_left'],
                'geometry' => ['width' => 199.0, 'height' => 228],
                'createdAt' => '2026-09-01T10:00:00Z',
                'modifiedAt' => '2026-09-02T11:30:00Z',
                'tagIds' => ['tag-1', 'tag-2'],
            ], [
                'id' => 'card-1',
                'type' => 'card',
                'data' => ['title' => 'Quest hook', 'description' => 'Find the relic'],
                'modifiedAt' => 'not-a-date',
            ]]]),
        ]);

        [$stickyNote, $card] = iterator_to_array($miroClient->iterateItems('board-1'), false);

        $this->assertSame('3458764517517819000', $stickyNote->id);
        $this->assertSame("Ser Aldric & the oath\nsecond line", $stickyNote->plainContent);
        $this->assertSame(100.5, $stickyNote->position?->x);
        $this->assertSame(-20.0, $stickyNote->position?->y);
        $this->assertSame('parent_top_left', $stickyNote->position?->relativeTo);
        $this->assertSame(228.0, $stickyNote->geometry?->height);
        $this->assertNull($stickyNote->geometry?->rotation);
        $this->assertSame('2026-09-02T11:30:00+00:00', $stickyNote->modifiedAt?->format(DATE_ATOM));
        $this->assertSame(['tag-1', 'tag-2'], $stickyNote->tagIds);
        $this->assertNull($stickyNote->parentId);

        $this->assertSame('Quest hook', $card->title);
        $this->assertSame('Find the relic', $card->plainContent);
        $this->assertNull($card->position);
        $this->assertNull($card->modifiedAt);
        $this->assertSame([], $card->tagIds);
    }

    public function testParsesConnectorCaptions(): void
    {
        $miroClient = $this->createMiroClient([
            $this->jsonResponse([
                'data' => [[
                    'id' => 'connector-1',
                    'type' => 'connector',
                    'startItem' => ['id' => 'item-a', 'position' => ['x' => '50%', 'y' => '0%']],
                    'endItem' => ['id' => 'item-b'],
                    'shape' => 'curved',
                    'captions' => [
                        ['content' => '<p>betrays</p>', 'position' => '50%', 'textAlignVertical' => 'middle'],
                        ['content' => 'secretly'],
                    ],
                ]],
                'limit' => 50,
                'size' => 1,
            ]),
        ]);

        $connectors = iterator_to_array($miroClient->iterateConnectors('board-1'), false);

        $this->assertCount(1, $connectors);
        $connector = $connectors[0];
        $this->assertSame('item-a', $connector->startItemId);
        $this->assertSame('item-b', $connector->endItemId);
        $this->assertSame('curved', $connector->shape);
        $this->assertCount(2, $connector->captions);
        $this->assertSame('<p>betrays</p>', $connector->captions[0]->content);
        $this->assertSame('50%', $connector->captions[0]->position);
        $this->assertNull($connector->captions[1]->position);
        $this->assertSame('betrays secretly', $connector->getCaptionText());
        $this->assertStringStartsWith('https://api.miro.com/v2/boards/board-1/connectors?', $this->sentRequests[0]['url']);
    }

    public function testRetriesAfterRateLimitUsingResetHeader(): void
    {
        $miroClient = $this->createMiroClient([
            new MockResponse('{"status":429,"code":"tooManyRequests"}', [
                'http_code' => 429,
                'response_headers' => ['X-RateLimit-Reset' => (string) (self::NOW + 7)],
            ]),
            $this->jsonResponse(['id' => 'board-1', 'name' => 'Story map', 'team' => ['id' => 'team-1']]),
        ]);

        $board = $miroClient->getBoard('board-1');

        $this->assertSame('Story map', $board->name);
        $this->assertSame('team-1', $board->teamId);
        $this->assertSame([7], $this->sleptSeconds);
        $this->assertCount(2, $this->sentRequests);
    }

    public function testGivesUpAfterRepeatedRateLimits(): void
    {
        $rateLimitedResponses = array_map(
            static fn (): MockResponse => new MockResponse('{}', ['http_code' => 429]),
            range(1, 4),
        );
        $miroClient = $this->createMiroClient($rateLimitedResponses);

        try {
            $miroClient->getBoard('board-1');
            $this->fail('Expected rate limit exception');
        } catch (MiroRateLimitExceededException $exception) {
            $this->assertSame(429, $exception->getHttpStatusCode());
        }

        $this->assertSame([1, 2, 4], $this->sleptSeconds);
    }

    public function testUnauthorizedResponseRaisesAuthorizationException(): void
    {
        $miroClient = $this->createMiroClient([
            new MockResponse('{"status":401,"code":"tokenNotProvided","message":"Unauthorized"}', ['http_code' => 401]),
        ]);

        $this->expectException(MiroAuthorizationException::class);

        iterator_to_array($miroClient->iterateItems('board-1'));
    }

    public function testOtherErrorsRaiseApiExceptionWithMessage(): void
    {
        $miroClient = $this->createMiroClient([
            new MockResponse('{"status":404,"code":"notFound","message":"Board not found"}', ['http_code' => 404]),
        ]);

        try {
            $miroClient->getBoard('missing');
            $this->fail('Expected API exception');
        } catch (MiroAuthorizationException) {
            $this->fail('404 must not be treated as an authorization failure');
        } catch (MiroApiException $exception) {
            $this->assertSame(404, $exception->getHttpStatusCode());
            $this->assertStringContainsString('Board not found', $exception->getMessage());
        }
    }

    public function testWalksOffsetPaginatedBoardsAndTags(): void
    {
        $miroClient = $this->createMiroClient([
            $this->jsonResponse(['data' => [['id' => 'board-1', 'name' => 'A'], ['id' => 'board-2', 'name' => 'B']], 'total' => 3, 'size' => 2, 'offset' => 0, 'limit' => 2]),
            $this->jsonResponse(['data' => [['id' => 'board-3', 'name' => 'C']], 'total' => 3, 'size' => 1, 'offset' => 2, 'limit' => 2]),
            $this->jsonResponse(['data' => [['id' => 'tag-1', 'title' => 'NPC', 'fillColor' => 'red', 'type' => 'tag']], 'total' => 1, 'size' => 1, 'offset' => 0]),
        ]);

        $boards = iterator_to_array($miroClient->iterateBoards('team-1'), false);
        $tags = iterator_to_array($miroClient->iterateTags('board-1'), false);

        $this->assertSame(['board-1', 'board-2', 'board-3'], array_map(static fn ($board): string => $board->id, $boards));
        $this->assertStringContainsString('team_id=team-1', $this->sentRequests[0]['url']);
        $this->assertStringContainsString('offset=2', $this->sentRequests[1]['url']);
        $this->assertCount(1, $tags);
        $this->assertSame('NPC', $tags[0]->title);
        $this->assertSame('red', $tags[0]->fillColor);
    }

    public function testReadsItemTags(): void
    {
        $miroClient = $this->createMiroClient([
            $this->jsonResponse(['tags' => [['id' => 'tag-1', 'title' => 'Faction', 'fillColor' => 'blue', 'type' => 'tag']]]),
        ]);

        $itemTags = $miroClient->getItemTags('board-1', 'item-1');

        $this->assertCount(1, $itemTags);
        $this->assertSame('Faction', $itemTags[0]->title);
        $this->assertSame('https://api.miro.com/v2/boards/board-1/items/item-1/tags', $this->sentRequests[0]['url']);
    }

    /**
     * @param list<MockResponse> $responses
     */
    private function createMiroClient(array $responses): MiroApiClient
    {
        $responseQueue = $responses;
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$responseQueue): MockResponse {
            $this->sentRequests[] = ['method' => $method, 'url' => $url, 'headers' => $options['headers'] ?? []];
            $nextResponse = array_shift($responseQueue);
            if ($nextResponse === null) {
                $this->fail('Unexpected extra request to ' . $url);
            }

            return $nextResponse;
        });

        return new MiroApiClient(
            $httpClient,
            'test-token',
            function (int $seconds): void {
                $this->sleptSeconds[] = $seconds;
            },
            static fn (): int => self::NOW,
        );
    }

    /**
     * @param array<mixed> $payload
     */
    private function jsonResponse(array $payload): MockResponse
    {
        return new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR), [
            'http_code' => 200,
            'response_headers' => ['Content-Type' => 'application/json'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function itemPayload(string $itemId): array
    {
        return [
            'id' => $itemId,
            'type' => 'sticky_note',
            'data' => ['content' => '<p>' . $itemId . '</p>'],
        ];
    }
}
