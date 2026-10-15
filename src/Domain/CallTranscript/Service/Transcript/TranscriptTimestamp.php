<?php

namespace App\Domain\CallTranscript\Service\Transcript;

final class TranscriptTimestamp
{
    public const PATTERN = '(?:\d+:)?\d{1,2}:\d{2}(?:[.,]\d+)?';

    public static function toSeconds(string $timestamp): float
    {
        $parts = explode(':', str_replace(',', '.', trim($timestamp)));
        $seconds = 0.0;
        foreach ($parts as $part) {
            $seconds = $seconds * 60 + (float) $part;
        }

        return $seconds;
    }

    public static function format(float $seconds): string
    {
        $wholeSeconds = max(0, (int) floor($seconds));

        return sprintf('%02d:%02d:%02d', intdiv($wholeSeconds, 3600), intdiv($wholeSeconds % 3600, 60), $wholeSeconds % 60);
    }
}
