<?php

namespace App\Domain\Integrations\Entity\Enum;

enum ExternalSourceSyncStatus: string
{
    case Success = 'success';
    case Partial = 'partial';
    case Failed = 'failed';
}
