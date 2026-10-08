<?php

declare(strict_types=1);

namespace App\Domain\Core\Service\Helper;

final class DateFormat
{
    public const DATE = 'd-m-Y';

    public const DATETIME = 'd-m-Y H:i';

    public const TIME = 'H:i';

    public const ISO_DATE = 'Y-m-d';

    public const ISO_DATETIME = 'Y-m-d H:i';

    public const ISO_DATETIME_SECONDS = 'Y-m-d H:i:s';

    public const FORM_DATE = 'yyyy-MM-dd';

    public const FORM_DATETIME = 'yyyy-MM-dd HH:mm';
}
