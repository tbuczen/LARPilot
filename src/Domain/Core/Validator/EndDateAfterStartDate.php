<?php

declare(strict_types=1);

namespace App\Domain\Core\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class EndDateAfterStartDate extends Constraint
{
    public string $message = 'larp.error.end_date_before_start_date';

    public string $translationDomain = 'validators';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
