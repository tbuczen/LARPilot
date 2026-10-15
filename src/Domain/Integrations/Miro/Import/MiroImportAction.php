<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

enum MiroImportAction: string
{
    case Link = 'link';
    case Create = 'create';
    case CreateRelation = 'create_relation';
    case Unchanged = 'unchanged';
    case Unmapped = 'unmapped';
    case NeedsType = 'needs_type';
    case Incompatible = 'incompatible';

    public function isApplicable(): bool
    {
        return in_array($this, [self::Link, self::Create, self::CreateRelation], true);
    }
}
