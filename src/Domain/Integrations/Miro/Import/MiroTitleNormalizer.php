<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

use Transliterator;

final class MiroTitleNormalizer
{
    private static ?Transliterator $asciiFolder = null;

    public static function normalize(?string $title): string
    {
        $folded = self::fold(self::exact($title));
        $alphanumeric = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $folded) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $alphanumeric) ?? '');
    }

    public static function exact(?string $title): string
    {
        if ($title === null) {
            return '';
        }

        $plainTitle = html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $plainTitle) ?? '');
    }

    private static function fold(string $title): string
    {
        self::$asciiFolder ??= Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
        if (!self::$asciiFolder instanceof Transliterator) {
            return mb_strtolower($title);
        }

        $folded = self::$asciiFolder->transliterate($title);

        return $folded === false ? mb_strtolower($title) : $folded;
    }
}
