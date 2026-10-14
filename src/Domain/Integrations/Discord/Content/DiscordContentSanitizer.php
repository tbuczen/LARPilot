<?php

namespace App\Domain\Integrations\Discord\Content;

use App\Domain\Core\Service\Helper\DateFormat;

final class DiscordContentSanitizer
{
    public const MAX_LENGTH = 2000;

    /**
     * @param array<string, string> $mentionedUserNames
     */
    public static function sanitize(string $content, array $mentionedUserNames = [], int $maxLength = self::MAX_LENGTH): string
    {
        $text = preg_replace('/<a?:(\w+):\d+>/u', ':$1:', $content) ?? '';
        $text = preg_replace_callback(
            '/<@!?(\d+)>/',
            static fn (array $match): string => '@' . ($mentionedUserNames[$match[1]] ?? 'user'),
            $text,
        ) ?? '';
        $text = preg_replace('/<@&\d+>/', '@role', $text) ?? '';
        $text = preg_replace('/<#\d+>/', '#channel', $text) ?? '';
        $text = preg_replace_callback(
            '/<t:(-?\d+)(?::[tTdDfFR])?>/',
            static fn (array $match): string => (new \DateTimeImmutable('@' . $match[1]))->format(DateFormat::DATETIME),
            $text,
        ) ?? '';
        $text = preg_replace('/<(https?:\/\/[^\s>]+)>/', '$1', $text) ?? '';
        $text = preg_replace('/```[a-z0-9_+-]*\n?/i', '', $text) ?? '';
        $text = preg_replace('/(\*\*|__|~~|\|\||`)/', '', $text) ?? '';
        $text = preg_replace('/(?<![\w*])\*(?!\s)([^*\n]+?)(?<!\s)\*(?![\w*])/u', '$1', $text) ?? '';
        $text = preg_replace('/^\s*(?:#{1,3}|-#|>>>|>)\s+/mu', '', $text) ?? '';
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? '';
        $text = trim(preg_replace('/\s*\n\s*/u', "\n", $text) ?? '');

        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $maxLength - 1)) . '…';
    }
}
