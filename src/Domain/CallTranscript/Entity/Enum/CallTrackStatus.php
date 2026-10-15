<?php

namespace App\Domain\CallTranscript\Entity\Enum;

enum CallTrackStatus: string
{
    case PENDING = 'pending';
    case TRANSCRIBED = 'transcribed';
    case FAILED = 'failed';
}
