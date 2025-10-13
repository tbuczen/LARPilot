<?php

namespace App\Domain\Integrations\Entity\Enum;

enum ReferenceRole: string
{
    case Primary = 'primary';   // main data source
    case Mention = 'mention';   // secondary, optional
    case Notes = 'notes';       // e.g. character doc
}
