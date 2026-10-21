<?php

namespace App\Domain\CallTranscript\DTO;

final readonly class CallRecordingOptions
{
    public function __construct(
        public bool $summary = true,
    ) {
    }
}
