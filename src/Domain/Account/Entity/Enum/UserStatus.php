<?php

namespace App\Domain\Account\Entity\Enum;

enum UserStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case SUSPENDED = 'suspended';
    case BANNED = 'banned';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::SUSPENDED => 'Suspended',
            self::BANNED => 'Banned',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::SUSPENDED => 'secondary',
            self::BANNED => 'danger',
        };
    }
}
