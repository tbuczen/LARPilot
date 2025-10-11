<?php

namespace App\Validator;

use App\Repository\StoryObject\CharacterRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueCharacterNameValidator extends ConstraintValidator
{
    public function __construct(private readonly CharacterRepository $repository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var $constraint UniqueCharacterName */
        if (null === $value || '' === $value) {
            return;
        }

        $character = $this->context->getObject(); // The current Character entity

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
