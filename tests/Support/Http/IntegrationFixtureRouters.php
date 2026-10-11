<?php

declare(strict_types=1);

namespace Tests\Support\Http;

final class IntegrationFixtureRouters
{
    public const DISCORD_BASE_URI = 'https://discord.com/api/v10/';
    public const MIRO_BASE_URI = 'https://api.miro.com/v2/';

    public const DISCORD_GUILD_ID = '1100000000000000001';
    public const DISCORD_FORUM_CHANNEL_ID = '1100000000000000200';
    public const DISCORD_THREAD_ID = '1100000000000000301';

    public const MIRO_BOARD_ID = 'uXjVOD6LSME=';
    public const MIRO_FRAME_ID = '3458764600000000001';
    public const MIRO_ITEMS_NEXT_CURSOR = 'MzQ1ODc2NDYwMDAwMDAwMDAxMX4=';

    public const DISCORD_RATE_LIMIT_HEADERS = [
        'retry-after' => '2',
        'x-ratelimit-limit' => '5',
        'x-ratelimit-remaining' => '0',
        'x-ratelimit-reset' => '1760200000.500',
        'x-ratelimit-reset-after' => '1.500',
        'x-ratelimit-bucket' => 'b8f6c4ad0f8c7f1d2e3a4b5c6d7e8f90',
        'x-ratelimit-scope' => 'user',
    ];

    public const MIRO_RATE_LIMIT_HEADERS = [
        'x-ratelimit-limit' => '100000',
        'x-ratelimit-remaining' => '0',
        'x-ratelimit-reset' => '1760200060',
    ];

    public static function fixtureDirectory(string $provider): string
    {
        return dirname(__DIR__) . '/Data/Integrations/' . $provider;
    }

    public static function discord(): FixtureRouter
    {
        return (new FixtureRouter(self::fixtureDirectory('discord'), '/api/v10'))
            ->on('GET', '/guilds/{guild_id}', 'guild.json')
            ->on('GET', '/guilds/{guild_id}/channels', 'channels.json')
            ->on('GET', '/guilds/{guild_id}/threads/active', 'threads_active.json')
            ->on('GET', '/channels/{channel_id}/threads/archived/public', 'threads_archived_public.json')
            ->on('GET', '/channels/{channel_id}/messages', 'messages.json');
    }

    public static function miro(): FixtureRouter
    {
        return (new FixtureRouter(self::fixtureDirectory('miro'), '/v2'))
            ->on('GET', '/boards', 'boards.json')
            ->on('GET', '/boards/{board_id}/items', 'board_items_page_1.json')
            ->on('GET', '/boards/{board_id}/items?cursor=' . rawurlencode(self::MIRO_ITEMS_NEXT_CURSOR), 'board_items_page_2.json')
            ->on('GET', '/boards/{board_id}/items?parent_item_id=' . self::MIRO_FRAME_ID, 'frame_children.json')
            ->on('GET', '/boards/{board_id}/connectors', 'connectors.json')
            ->on('GET', '/boards/{board_id}/tags', 'tags.json');
    }
}
