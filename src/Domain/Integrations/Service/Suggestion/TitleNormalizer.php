<?php

namespace App\Domain\Integrations\Service\Suggestion;

use function Symfony\Component\String\u;

final class TitleNormalizer
{
    public static function normalize(?string $title): string
    {
        if ($title === null || trim($title) === '') {
            return '';
        }

        $folded = u($title)->ascii()->lower()->toString();

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $folded));
    }

    public static function containsPhrase(string $normalizedHaystack, string $normalizedPhrase): bool
    {
        if ($normalizedHaystack === '' || $normalizedPhrase === '') {
            return false;
        }

        return str_contains(' ' . $normalizedHaystack . ' ', ' ' . $normalizedPhrase . ' ');
    }
}
