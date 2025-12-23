<?php

namespace App\Domain\StoryObject\Validator;

use App\Domain\StoryObject\Repository\CharacterRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueCharacterNameValidator extends ConstraintValidator
{
    public function __construct(private readonly CharacterRepository $repository)
    {
    }

    /**
     * @param UniqueCharacterName $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
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
