<?php

namespace App\Domain\Integrations\Service\Url;

use App\Domain\Integrations\DTO\ParsedExternalUrl;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceType;

final class ExternalUrlParser
{
    private const DISCORD_HOSTS = ['discord.com', 'www.discord.com', 'ptb.discord.com', 'canary.discord.com', 'discordapp.com', 'www.discordapp.com'];

    private const MIRO_HOSTS = ['miro.com', 'www.miro.com'];

    public function parse(string $url): ?ParsedExternalUrl
    {
        $trimmedUrl = trim($url);
        $parts = parse_url($trimmedUrl);
        if ($parts === false || !isset($parts['host'], $parts['scheme'])) {
            return null;
        }

        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '';

        if (in_array($host, self::DISCORD_HOSTS, true)) {
            return $this->parseDiscord($path);
        }

        if (in_array($host, self::MIRO_HOSTS, true)) {
            return $this->parseMiro($path, $parts['query'] ?? '');
        }

        return null;
    }

    private function parseDiscord(string $path): ?ParsedExternalUrl
    {
        if (preg_match('#^/channels/(\d+)/(\d+)(?:/(\d+))?/?$#', $path, $matches) !== 1) {
            return null;
        }

        $guildId = $matches[1];
        $channelId = $matches[2];
        $messageId = $matches[3] ?? '';
        $canonicalUrl = sprintf('https://discord.com/channels/%s/%s', $guildId, $channelId);

        if ($messageId === '') {
            return new ParsedExternalUrl(LarpIntegrationProvider::Discord, $channelId, ReferenceType::Thread, $canonicalUrl);
        }

        return new ParsedExternalUrl(LarpIntegrationProvider::Discord, $messageId, ReferenceType::Comment, $canonicalUrl . '/' . $messageId);
    }

    private function parseMiro(string $path, string $query): ?ParsedExternalUrl
    {
        if (preg_match('#^/app/board/([A-Za-z0-9_=\-]+)/?$#', $path, $matches) !== 1) {
            return null;
        }

        $boardId = $matches[1];
        $boardUrl = sprintf('https://miro.com/app/board/%s/', $boardId);

        parse_str($query, $queryParameters);
        $widgetId = $queryParameters['moveToWidget'] ?? null;
        if (!is_string($widgetId) || preg_match('/^\d+$/', $widgetId) !== 1) {
            return new ParsedExternalUrl(LarpIntegrationProvider::Miro, $boardId, ReferenceType::Board, $boardUrl);
        }

        return new ParsedExternalUrl(LarpIntegrationProvider::Miro, $widgetId, ReferenceType::BoardItem, $boardUrl . '?moveToWidget=' . $widgetId);
    }
}
