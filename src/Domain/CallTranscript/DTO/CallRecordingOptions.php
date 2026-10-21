<?php

namespace App\Domain\CallTranscript\DTO;

use App\Domain\Integrations\Entity\SharedFile;

final readonly class CallRecordingOptions
{
    public function __construct(
        public bool $summary = true,
        public ?SharedFile $googleDocFolder = null,
        public ?string $discordChannelId = null,
    ) {
    }
}
