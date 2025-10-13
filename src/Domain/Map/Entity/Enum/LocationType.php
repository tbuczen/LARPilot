<?php

namespace App\Domain\Map\Entity\Enum;

use App\Domain\Core\Entity\Enum\LabelableEnumInterface;

enum LocationType: string implements LabelableEnumInterface
{
    case INDOOR = 'indoor';
    case OUTDOOR = 'outdoor';
    case SPECIAL = 'special';
    case TRANSITION = 'transition';

    public function getLabel(): string
    {
        return match ($this) {
            self::INDOOR => 'Indoor',
            self::OUTDOOR => 'Outdoor',
            self::SPECIAL => 'Special',
            self::TRANSITION => 'Transition',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::INDOOR => 'bi-house',
            self::OUTDOOR => 'bi-tree',
            self::SPECIAL => 'bi-star',
            self::TRANSITION => 'bi-arrow-left-right',
        };
    }
}
