<?php

namespace App\Domain\Core\Entity\Enum;

enum LarpStageStatus: string
{
    case DRAFT = 'DRAFT'; #just created
    case WIP = 'WIP'; #work in progress - visible for all organizers team
    case PUBLISHED = 'PUBLISHED'; #work in progress - visible for everyone
    case INQUIRIES = 'INQUIRIES'; #stage for collecting player inquiries
    case CONFIRMED = 'CONFIRMED'; #confirmed that it will happen
    case NEGOTIATION = 'NEGOTIATION'; #optional post-application character customization discussion
    case COSTUME_CHECK = 'COSTUME_CHECK'; #optional costume commissioning/verification stage
    case IN_PROGRESS = 'IN_PROGRESS'; #event is currently running
    case CANCELLED = 'CANCELLED'; #cancelled after confirmation that it will happen
    case COMPLETED = 'COMPLETED'; #completed/ happened after confirmation that it will happen

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::WIP => 'warning',
            self::PUBLISHED => 'primary',
            self::INQUIRIES => 'info',
            self::NEGOTIATION => 'warning',
            self::COSTUME_CHECK => 'warning',
            self::IN_PROGRESS => 'success',
            self::CONFIRMED => 'success',
            self::CANCELLED => 'danger',
            self::COMPLETED => 'dark',
        };
    }

    public function isVisibleForEveryone(): bool
    {
        return match ($this) {
            self::PUBLISHED, self::INQUIRIES, self::CONFIRMED, self::NEGOTIATION,
            self::COSTUME_CHECK, self::IN_PROGRESS, self::COMPLETED => true,
            default => false,
        };
    }

    public function isVisibleForOrganizers(): bool
    {
        return match ($this) {
            self::WIP, self::PUBLISHED, self::INQUIRIES, self::CONFIRMED, self::NEGOTIATION,
            self::COSTUME_CHECK, self::IN_PROGRESS, self::COMPLETED => true,
            default => false,
        };
    }
}
