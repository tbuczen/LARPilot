<?php

declare(strict_types=1);

namespace App\Domain\Core\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type\DateType;

class DatePickerExtension extends AbstractDatePickerExtension
{
    public const FORMAT = 'yyyy-MM-dd';

    public static function getExtendedTypes(): iterable
    {
        return [DateType::class];
    }

    protected function format(): string
    {
        return self::FORMAT;
    }

    protected function withTime(): bool
    {
        return false;
    }
}
