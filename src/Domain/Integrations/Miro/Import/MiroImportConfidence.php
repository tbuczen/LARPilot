<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

enum MiroImportConfidence: string
{
    case High = 'high';
    case Low = 'low';
}
