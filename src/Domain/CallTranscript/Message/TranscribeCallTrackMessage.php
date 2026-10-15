<?php

namespace App\Domain\CallTranscript\Message;

use Symfony\Component\Uid\Uuid;

final readonly class TranscribeCallTrackMessage
{
    public function __construct(
        public Uuid $trackId,
    ) {
    }
}
