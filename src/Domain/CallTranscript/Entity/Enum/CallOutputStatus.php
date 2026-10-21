<?php

namespace App\Domain\CallTranscript\Entity\Enum;

enum CallOutputStatus: string
{
    case SKIPPED = 'skipped';
    case PENDING = 'pending';
    case DONE = 'done';
    case FAILED = 'failed';

    public function isRetryable(): bool
    {
        return $this === self::FAILED;
    }
}
