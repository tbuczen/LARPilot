<?php

namespace App\Domain\Integrations\Entity\Enum;

enum ExternalItemAvailability: string
{
    case Ok = 'ok';
    case Archived = 'archived';
    case Changed = 'changed';
    case Gone = 'gone';
    case Forbidden = 'forbidden';
}
