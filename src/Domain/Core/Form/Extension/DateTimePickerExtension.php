<?php

declare(strict_types=1);

namespace App\Domain\Core\Form\Extension;

use App\Domain\Core\Service\Helper\DateFormat;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class DateTimePickerExtension extends AbstractDatePickerExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [DateTimeType::class];
    }

    protected function format(): string
    {
        return DateFormat::FORM_DATETIME;
    }

    protected function withTime(): bool
    {
        return true;
    }
}
