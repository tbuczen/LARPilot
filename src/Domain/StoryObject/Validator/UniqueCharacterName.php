<?php

namespace App\Domain\StoryObject\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class UniqueCharacterName extends Constraint
{
    public string $message = 'larp.character.error.unique_name';

    public string $translationDomain = 'validators';
}
