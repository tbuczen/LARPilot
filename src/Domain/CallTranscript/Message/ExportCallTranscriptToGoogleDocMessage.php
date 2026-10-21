<?php

namespace App\Domain\CallTranscript\Message;

use Symfony\Component\Uid\Uuid;

final readonly class ExportCallTranscriptToGoogleDocMessage
{
    public function __construct(
        public Uuid $recordingId,
    ) {
    }
}
