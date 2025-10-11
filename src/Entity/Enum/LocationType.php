<?php

namespace App\Entity\Enum;

enum LocationType: string
{
    case INDOOR = 'indoor';
    case OUTDOOR = 'outdoor';
    case SPECIAL = 'special';
    case TRANSITION = 'transition';
}
