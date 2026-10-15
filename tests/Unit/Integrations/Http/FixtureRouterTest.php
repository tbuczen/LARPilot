<?php

declare(strict_types=1);

namespace Tests\Unit\Integrations\Http;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Tests\Support\Http\IntegrationFixtureRouters;

class FixtureRouterTest extends TestCase
{
    public function testServesDiscordGuildFixture(): void
    {
        $client = new MockHttpClient(IntegrationFixtureRouters::discord(), IntegrationFixtureRouters::DISCORD_BASE_URI);

        $response = $client->request('GET', 'guilds/' . IntegrationFixtureRouters::DISCORD_GUILD_ID);
        $guild = $response->toArray();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaders()['content-type'][0]);
        $this->assertSame(IntegrationFixtureRouters::DISCORD_GUILD_ID, $guild['id']);
    }

    public function testServesDiscordForumChannelWithTags(): void
    {
        $client = new MockHttpClient(IntegrationFixtureRouters::discord(), IntegrationFixtureRouters::DISCORD_BASE_URI);

        $channels = $client->request('GET', 'guilds/' . IntegrationFixtureRouters::DISCORD_GUILD_ID . '/channels')->toArray();
        $forums = array_values(array_filter($channels, static fn (array $channel): bool => $channel['type'] === 15));

        $this->assertCount(1, $forums);
        $this->assertSame(IntegrationFixtureRouters::DISCORD_FORUM_CHANNEL_ID, $forums[0]['id']);
        $this->assertNotEmpty($forums[0]['available_tags']);
    }

    public function testServesDiscordMessagesRegardlessOfQuery(): void
    {
        $router = IntegrationFixtureRouters::discord();
        $client = new MockHttpClient($router, IntegrationFixtureRouters::DISCORD_BASE_URI);

        $messages = $client->request('GET', 'channels/' . IntegrationFixtureRouters::DISCORD_THREAD_ID . '/messages', [
            'query' => ['limit' => 100],
        ])->toArray();

        $this->assertCount(3, $messages);
        $this->assertSame([
            'method' => 'GET',
            'path' => '/channels/' . IntegrationFixtureRouters::DISCORD_THREAD_ID . '/messages',
            'query' => ['limit' => '100'],
        ], $router->getRequests()[0]);
    }

    public function testOverriddenDiscordRouteServesRateLimit(): void
    {
        $router = IntegrationFixtureRouters::discord()
            ->on('GET', '/guilds/{guild_id}/channels', 'rate_limited.json', 429, IntegrationFixtureRouters::DISCORD_RATE_LIMIT_HEADERS);
        $client = new MockHttpClient($router, IntegrationFixtureRouters::DISCORD_BASE_URI);

        $response = $client->request('GET', 'guilds/' . IntegrationFixtureRouters::DISCORD_GUILD_ID . '/channels');

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('2', $response->getHeaders(false)['retry-after'][0]);
        $this->assertSame(1.5, $response->toArray(false)['retry_after']);
    }

    public function testServesMiroItemsPagesByCursor(): void
    {
        $client = new MockHttpClient(IntegrationFixtureRouters::miro(), IntegrationFixtureRouters::MIRO_BASE_URI);
        $itemsPath = 'boards/' . rawurlencode(IntegrationFixtureRouters::MIRO_BOARD_ID) . '/items';

        $firstPage = $client->request('GET', $itemsPath, ['query' => ['limit' => 10]])->toArray();
        $secondPage = $client->request('GET', $itemsPath, [
            'query' => ['limit' => 10, 'cursor' => $firstPage['cursor']],
        ])->toArray();

        $this->assertSame(IntegrationFixtureRouters::MIRO_ITEMS_NEXT_CURSOR, $firstPage['cursor']);
        $this->assertSame('frame', $firstPage['data'][0]['type']);
        $this->assertArrayNotHasKey('cursor', $secondPage);
        $this->assertSame('card', $secondPage['data'][0]['type']);
    }

    public function testServesMiroFrameChildren(): void
    {
        $client = new MockHttpClient(IntegrationFixtureRouters::miro(), IntegrationFixtureRouters::MIRO_BASE_URI);

        $children = $client->request('GET', 'boards/' . rawurlencode(IntegrationFixtureRouters::MIRO_BOARD_ID) . '/items', [
            'query' => ['parent_item_id' => IntegrationFixtureRouters::MIRO_FRAME_ID],
        ])->toArray();

        foreach ($children['data'] as $child) {
            $this->assertSame(IntegrationFixtureRouters::MIRO_FRAME_ID, $child['parent']['id']);
        }
        $this->assertCount(2, $children['data']);
    }

    public function testOverriddenMiroRouteServesUnauthorized(): void
    {
        $router = IntegrationFixtureRouters::miro()->on('GET', '/boards', 'unauthorized.json', 401);
        $client = new MockHttpClient($router, IntegrationFixtureRouters::MIRO_BASE_URI);

        $response = $client->request('GET', 'boards');

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('error', $response->toArray(false)['type']);
    }

    public function testUnknownRouteFailsLoudly(): void
    {
        $client = new MockHttpClient(IntegrationFixtureRouters::miro(), IntegrationFixtureRouters::MIRO_BASE_URI);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No fixture route registered for DELETE /boards');

        $client->request('DELETE', 'boards');
    }
}
