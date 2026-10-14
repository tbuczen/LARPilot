<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Snapshot;

final class MiroBoardUrls
{
    public static function board(string $boardId): string
    {
        return sprintf('https://miro.com/app/board/%s/', $boardId);
    }

    public static function item(string $boardId, string $itemId): string
    {
        return self::board($boardId) . '?moveToWidget=' . rawurlencode($itemId);
    }

    public static function liveEmbed(string $boardId): string
    {
        return sprintf('https://miro.com/app/live-embed/%s/?autoplay=true', $boardId);
    }
}
