<?php

namespace App\Domain\CallTranscript\Service\Transcript;

use App\Domain\CallTranscript\DTO\TranscriptSegment;

final class SubtitleParser
{
    private const CUE_TIMING_PATTERN = '/^\s*(' . TranscriptTimestamp::PATTERN . ')\s*-->\s*(' . TranscriptTimestamp::PATTERN . ')/';
    private const TEXT_LINE_PATTERN = '/^\s*\[?(' . TranscriptTimestamp::PATTERN . ')\]?\s*[-:]?\s*(.*)$/';

    /**
     * @return list<TranscriptSegment>
     */
    public function parse(string $content, string $format): array
    {
        $content = (string) preg_replace('/^\xEF\xBB\xBF/', '', $content);

        return match (strtolower($format)) {
            'srt', 'vtt' => $this->parseCues($content),
            default => $this->parsePlainText($content),
        };
    }

    /**
     * @return list<TranscriptSegment>
     */
    private function parseCues(string $content): array
    {
        $segments = [];
        $blocks = preg_split('/\R\s*\R/', trim($content)) ?: [];
        foreach ($blocks as $block) {
            $lines = preg_split('/\R/', $block) ?: [];
            $timingIndex = null;
            foreach ($lines as $lineIndex => $line) {
                if (preg_match(self::CUE_TIMING_PATTERN, $line, $matches) === 1) {
                    $timingIndex = $lineIndex;
                    break;
                }
            }

            if ($timingIndex === null || !isset($matches[1])) {
                continue;
            }

            $text = $this->cleanText(implode(' ', array_slice($lines, $timingIndex + 1)));
            if ($text === '') {
                continue;
            }

            $segments[] = new TranscriptSegment(TranscriptTimestamp::toSeconds($matches[1]), $text);
        }

        return $segments;
    }

    /**
     * @return list<TranscriptSegment>
     */
    private function parsePlainText(string $content): array
    {
        $segments = [];
        $lastStart = 0.0;
        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            $startSeconds = $lastStart;
            $text = $line;
            if (preg_match(self::TEXT_LINE_PATTERN, $line, $matches) === 1) {
                $startSeconds = TranscriptTimestamp::toSeconds($matches[1]);
                $text = $matches[2];
            }

            $text = $this->cleanText($text);
            if ($text === '') {
                continue;
            }

            $segments[] = new TranscriptSegment($startSeconds, $text);
            $lastStart = $startSeconds;
        }

        return $segments;
    }

    private function cleanText(string $text): string
    {
        $withoutTags = strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return trim((string) preg_replace('/\s+/u', ' ', $withoutTags));
    }
}
