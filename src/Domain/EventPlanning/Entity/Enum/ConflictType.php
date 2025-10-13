<?php

namespace App\Domain\EventPlanning\Entity\Enum;

use App\Domain\Core\Entity\Enum\LabelableEnumInterface;

enum ConflictType: string implements LabelableEnumInterface
{
    case RESOURCE_DOUBLE_BOOKING = 'resource_double_booking';
    case LOCATION_CAPACITY = 'location_capacity';
    case CHARACTER_IMPOSSIBLE = 'character_impossible';
    case TIMELINE_OVERLAP = 'timeline_overlap';
    case STAFF_OVERLOAD = 'staff_overload';

    public function getLabel(): string
    {
        return match ($this) {
            self::RESOURCE_DOUBLE_BOOKING => 'Resource Double Booking',
            self::LOCATION_CAPACITY => 'Location Capacity Exceeded',
            self::CHARACTER_IMPOSSIBLE => 'Character in Two Places',
            self::TIMELINE_OVERLAP => 'Timeline Overlap',
            self::STAFF_OVERLOAD => 'Staff Overload',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::RESOURCE_DOUBLE_BOOKING => 'bi-calendar-x',
            self::LOCATION_CAPACITY => 'bi-house-exclamation',
            self::CHARACTER_IMPOSSIBLE => 'bi-person-x',
            self::TIMELINE_OVERLAP => 'bi-clock-history',
            self::STAFF_OVERLOAD => 'bi-people-fill',
        };
    }
}
