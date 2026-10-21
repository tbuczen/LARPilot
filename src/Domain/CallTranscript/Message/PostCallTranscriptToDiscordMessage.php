<?php

namespace App\Domain\CallTranscript\Message;

use Symfony\Component\Uid\Uuid;

final readonly class PostCallTranscriptToDiscordMessage
{
    public function __construct(
        public Uuid $recordingId,
    ) {
    }
}
