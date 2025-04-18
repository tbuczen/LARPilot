<?php

namespace App\Validator;

use App\Repository\LarpCharacterRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueCharacterNameValidator extends ConstraintValidator
{
    public function __construct(private readonly LarpCharacterRepository $repository) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var $constraint UniqueCharacterName */
        if (null === $value || '' === $value) {
            return;
        }

        $character = $this->context->getObject(); // The current LarpCharacter entity

        if (!$character->getLarp()) {
            return;
        }

        $existing = $this->repository->findOneBy([
            'larp' => $character->getLarp(),
            'name' => $value,
        ]);

        if ($existing && $existing->getId() !== $character->getId()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ name }}', $value)
                ->addViolation();
        }
    }
}
