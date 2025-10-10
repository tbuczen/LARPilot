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

    public function truncateWords(?string $html, int $limit = 30, string $ellipsis = '…'): string
    {
        if ($html === null) {
            return '';
        }
        if ($limit <= 0) {
            return '';
        }

        // Fast path: if plain text word count is within limit, return original HTML
        $plain = trim((string) preg_replace('/\s+/u', ' ', strip_tags($html)));
        $words = preg_split('/\s/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) <= $limit) {
            return $html;
        }

        // Build plain cutoff string for matching
        $cutPlain = implode(' ', array_slice($words, 0, $limit));

        // Walk original HTML and collect until we reached the cutoff in plain text
        $out = '';
        $plainSoFar = '';
        $openTags = [];

        $tokens = preg_split('/(<[^>]+>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($tokens as $token) {
            if ($token !== '' && $token[0] === '<') {
                // HTML tag
                // Track open/close tags for proper closing later (ignore self-closing)
                if (preg_match('#^<\s*([a-zA-Z0-9:_-]+)(\s[^>]*)?>$#u', $token, $m)) {
                    $tag = strtolower($m[1]);
                    if (!preg_match('#/\s*>$#', $token)) { // not self-closing
                        $openTags[] = $tag;
                    }
                } elseif (preg_match('#^<\s*/\s*([a-zA-Z0-9:_-]+)\s*>$#u', $token, $m)) {
                    $tag = strtolower($m[1]);
                    // pop matching tag if present
                    for ($i = count($openTags) - 1; $i >= 0; $i--) {
                        if ($openTags[$i] === $tag) {
                            array_splice($openTags, $i, 1);
                            break;
                        }
                    }
                }
                $out .= $token;
                continue;
            }

            // Text node: append piece by piece until we reach cutoff
            $segments = preg_split('/(\s+)/u', $token, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($segments as $seg) {
                $isSpace = preg_match('/^\s+$/u', $seg) === 1;
                if ($isSpace) {
                    // normalize to single space in plain counter
                    if ($plainSoFar !== '' && substr($plainSoFar, -1) !== ' ') {
                        $plainSoFar .= ' ';
                    }
                    $out .= $seg;
                } else {
                    // add a word
                    $nextPlain = $plainSoFar === '' ? $seg : rtrim($plainSoFar) . ' ' . $seg;
                    if (mb_strlen($nextPlain) > mb_strlen($cutPlain)) {
                        // stop before overshooting; append ellipsis and close tags
                        $out .= $ellipsis;
                        foreach (array_reverse($openTags) as $tag) {
                            $out .= "</{$tag}>";
                        }
                        return $out;
                    }
                    $plainSoFar = $nextPlain;
                    $out .= $seg;
                }
            }
        }

        // Fallback (shouldn’t reach here often)
        $out .= $ellipsis;
        foreach (array_reverse($openTags) as $tag) {
            $out .= "</{$tag}>";
        }
        return $out;
    }
}
