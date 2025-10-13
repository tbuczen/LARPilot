<?php

namespace App\Domain\EventPlanning\Entity\Enum;

enum ConflictSeverity: string implements \App\Domain\Core\Entity\Enum\LabelableEnumInterface
{
    case CRITICAL = 'critical';
    case WARNING = 'warning';
    case INFO = 'info';

    public function getLabel(): string
    {
        return match ($this) {
            self::CRITICAL => 'Critical',
            self::WARNING => 'Warning',
            self::INFO => 'Info',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::CRITICAL => 'bg-danger',
            self::WARNING => 'bg-warning',
            self::INFO => 'bg-info',
        };
    }
}
