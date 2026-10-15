<?php

namespace App\Domain\CallTranscript\DTO;

final readonly class ImportedCallTrack
{
    public function __construct(
        public string $speaker,
        public ?string $audioFilename,
        public ?string $transcriptFilename,
    ) {
    }
}
