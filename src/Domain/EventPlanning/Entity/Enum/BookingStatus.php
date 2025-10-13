<?php

namespace App\Domain\EventPlanning\Entity\Enum;

use App\Domain\Core\Entity\Enum\LabelableEnumInterface;

enum BookingStatus: string implements LabelableEnumInterface
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CONFLICT = 'conflict';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::CONFLICT => 'Conflict',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-warning',
            self::CONFIRMED => 'bg-success',
            self::CONFLICT => 'bg-danger',
            self::CANCELLED => 'bg-secondary',
        };
    }
}
