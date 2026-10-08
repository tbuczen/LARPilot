<?php

declare(strict_types=1);

namespace App\Domain\Core\Form\Extension;

use App\Domain\Core\Service\Helper\DateFormat;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class DatePickerExtension extends AbstractDatePickerExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [DateType::class];
    }

    protected function format(): string
    {
        return DateFormat::FORM_DATE;
    }

    protected function withTime(): bool
    {
        return false;
    }
}
