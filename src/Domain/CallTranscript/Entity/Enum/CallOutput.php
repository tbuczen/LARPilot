<?php

namespace App\Domain\CallTranscript\Entity\Enum;

enum CallOutput: string
{
    case SUMMARY = 'summary';
    case GOOGLE_DOC = 'google_doc';
    case DISCORD = 'discord';
}
