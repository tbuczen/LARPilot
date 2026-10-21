<?php

namespace App\Domain\Core\Entity\Enum;

enum LarpCharacterSystem: string implements LabelableEnumInterface
{
    case SANDBOX = 'sandbox';
    case PREPARED_CHARACTERS = 'pre_written';

    public function getLabel(): string
    {
        return match ($this) {
            self::SANDBOX => 'enum.larp_character_system.sandbox',
            self::PREPARED_CHARACTERS => 'enum.larp_character_system.pre_written',
        };
    }
}
