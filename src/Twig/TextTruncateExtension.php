<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TextTruncateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('truncate_words', $this->truncateWords(...), ['is_safe' => ['html']]),
        ];
    }

    public function truncateWords(?string $text, int $limit = 30, string $ellipsis = '…'): string
    {
        if ($text === null) {
            return '';
        }

        // Normalize whitespace
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        if ($limit <= 0) {
            return '';
        }

        $words = preg_split('/\s/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) <= $limit) {
            return $text;
        }

        $cut = array_slice($words, 0, $limit);
        return implode(' ', $cut) . $ellipsis;
    }
}
