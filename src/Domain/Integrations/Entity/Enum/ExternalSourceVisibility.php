<?php

namespace App\Domain\Integrations\Entity\Enum;

enum ExternalSourceVisibility: string
{
    case OrganizerOnly = 'organizer_only';
    case PlayerFacing = 'player_facing';
}
