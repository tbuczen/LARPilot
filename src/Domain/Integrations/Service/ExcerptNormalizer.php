<?php

namespace App\Domain\Integrations\Service;

final class ExcerptNormalizer
{
    public const MAX_LENGTH = 500;

    public static function normalize(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $plainText = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainText = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $plainText) ?? '';
        $plainText = trim(preg_replace('/\s+/u', ' ', $plainText) ?? '');

        if ($plainText === '') {
            return null;
        }

        if (mb_strlen($plainText) <= self::MAX_LENGTH) {
            return $plainText;
        }

        return rtrim(mb_substr($plainText, 0, self::MAX_LENGTH - 1)) . '…';
    }
}
