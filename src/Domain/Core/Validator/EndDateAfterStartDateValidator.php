<?php

declare(strict_types=1);

namespace App\Domain\Core\Validator;

use App\Domain\Core\Entity\Larp;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EndDateAfterStartDateValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        assert($constraint instanceof EndDateAfterStartDate);

        if (!$value instanceof Larp) {
            return;
        }

        $startDate = $value->getStartDate();
        $endDate = $value->getEndDate();

        if ($startDate === null || $endDate === null) {
            return;
        }

        if ($endDate <= $startDate) {
            $this->context->buildViolation($constraint->message)
                ->atPath('endDate')
                ->addViolation();
        }
    }
}
