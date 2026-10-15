<?php

namespace App\Domain\CallTranscript\DTO;

final readonly class SpeakerTranscript
{
    /**
     * @param list<TranscriptSegment> $segments
     */
    public function __construct(
        public string $speaker,
        public array $segments,
    ) {
    }
}
