<?php

namespace App\Domain\CallTranscript\DTO;

use DateTimeImmutable;

final readonly class ImportedCallRecording
{
    /**
     * @param list<ImportedCallTrack> $tracks
     * @param list<TranscriptSegment> $notes
     */
    public function __construct(
        public array $tracks,
        public ?DateTimeImmutable $startedAt,
        public array $notes,
    ) {
    }
}
