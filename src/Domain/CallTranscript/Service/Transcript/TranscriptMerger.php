<?php

namespace App\Domain\CallTranscript\Service\Transcript;

use App\Domain\CallTranscript\DTO\SpeakerTranscript;

final class TranscriptMerger
{
    /**
     * @param list<SpeakerTranscript> $speakerTranscripts
     *
     * @return list<string>
     */
    public function merge(array $speakerTranscripts): array
    {
        $entries = [];
        foreach ($speakerTranscripts as $speakerTranscript) {
            foreach ($speakerTranscript->segments as $segment) {
                $entries[] = [
                    'start' => $segment->startSeconds,
                    'order' => count($entries),
                    'line' => sprintf('[%s] %s: %s', TranscriptTimestamp::format($segment->startSeconds), $speakerTranscript->speaker, $segment->text),
                ];
            }
        }

        usort($entries, static fn (array $left, array $right): int => [$left['start'], $left['order']] <=> [$right['start'], $right['order']]);

        return array_column($entries, 'line');
    }
}
