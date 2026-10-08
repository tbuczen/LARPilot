<?php

declare(strict_types=1);

namespace App\Domain\Core\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class DateTimePickerExtension extends AbstractDatePickerExtension
{
    public const FORMAT = 'yyyy-MM-dd HH:mm';

    public static function getExtendedTypes(): iterable
    {
        return [DateTimeType::class];
    }

    protected function format(): string
    {
        return self::FORMAT;
    }

    protected function withTime(): bool
    {
        return true;
    }
}
