<?php

namespace App\Domain\CallTranscript\Entity\Enum;

enum CallRecordingStatus: string
{
    case AWAITING_REVIEW = 'awaiting_review';
    case TRANSCRIBING = 'transcribing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
