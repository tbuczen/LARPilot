<?php

namespace App\Domain\Core\Entity\Enum;

enum LarpStageStatus: string
{
    case DRAFT = 'DRAFT'; #just created
    case WIP = 'WIP'; #work in progress - visible for all organizers team
    case PUBLISHED = 'PUBLISHED'; #work in progress - visible for everyone
    case INQUIRIES = 'INQUIRIES'; #stage for collecting player inquiries
    case CONFIRMED = 'CONFIRMED'; #confirmed that it will happen
    case CANCELLED = 'CANCELLED'; #cancelled by organizers at any stage before completion
    case COMPLETED = 'COMPLETED'; #completed/ happened after confirmation that it will happen

    public function isVisibleForEveryone(): bool
    {
        return match ($this) {
            self::PUBLISHED, self::INQUIRIES, self::CONFIRMED, self::COMPLETED => true,
            default => false,
        };
    }

    public function isVisibleForOrganizers(): bool
    {
        return match ($this) {
            self::WIP, self::PUBLISHED, self::INQUIRIES, self::CONFIRMED, self::COMPLETED => true,
            // Perhaps DRAFT is visible only to admins, and CANCELLED remains hidden for organizers.
            default => false,
        };
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }
}
