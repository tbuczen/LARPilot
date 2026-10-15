<?php

namespace App\Domain\Integrations\Entity\Enum;

enum IntegrationStatus: string
{
    case Active = 'active';
    case NeedsReauth = 'needs_reauth';
    case Error = 'error';
    case Disabled = 'disabled';

    public function isUsable(): bool
    {
        return $this === self::Active;
    }
}
