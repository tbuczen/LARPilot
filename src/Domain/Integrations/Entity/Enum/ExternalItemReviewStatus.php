<?php

namespace App\Domain\Integrations\Entity\Enum;

enum ExternalItemReviewStatus: string
{
    case Unreviewed = 'unreviewed';
    case Linked = 'linked';
    case Ignored = 'ignored';
}
