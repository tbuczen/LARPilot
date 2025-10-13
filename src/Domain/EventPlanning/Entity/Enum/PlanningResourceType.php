<?php

namespace App\Domain\EventPlanning\Entity\Enum;

use App\Domain\Core\Entity\Enum\LabelableEnumInterface;

enum PlanningResourceType: string implements LabelableEnumInterface
{
    case NPC = 'npc';
    case STAFF_GM = 'staff_gm';
    case STAFF_TECH = 'staff_tech';
    case STAFF_SAFETY = 'staff_safety';
    case STAFF_PHOTO = 'staff_photo';
    case PROP = 'prop';
    case EQUIPMENT = 'equipment';
    case VEHICLE = 'vehicle';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::NPC => 'NPC/Character',
            self::STAFF_GM => 'Game Master',
            self::STAFF_TECH => 'Technician',
            self::STAFF_SAFETY => 'Safety Marshal',
            self::STAFF_PHOTO => 'Photographer',
            self::PROP => 'Prop',
            self::EQUIPMENT => 'Equipment',
            self::VEHICLE => 'Vehicle',
            self::OTHER => 'Other',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::NPC => 'bi-person',
            self::STAFF_GM => 'bi-briefcase',
            self::STAFF_TECH => 'bi-tools',
            self::STAFF_SAFETY => 'bi-shield-check',
            self::STAFF_PHOTO => 'bi-camera',
            self::PROP => 'bi-box',
            self::EQUIPMENT => 'bi-gear',
            self::VEHICLE => 'bi-truck',
            self::OTHER => 'bi-three-dots',
        };
    }
}
