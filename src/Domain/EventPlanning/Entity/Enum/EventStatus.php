<?php

namespace App\Domain\EventPlanning\Entity\Enum;

use App\Domain\Core\Entity\Enum\LabelableEnumInterface;

enum EventStatus: string implements LabelableEnumInterface
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::CONFIRMED => 'Confirmed',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-secondary',
            self::CONFIRMED => 'bg-success',
            self::IN_PROGRESS => 'bg-primary',
            self::COMPLETED => 'bg-info',
            self::CANCELLED => 'bg-danger',
        };
    }
}
