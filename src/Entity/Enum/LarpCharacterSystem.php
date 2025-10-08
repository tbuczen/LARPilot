<?php

namespace App\Entity\Enum;

enum LarpCharacterSystem: string
{
    case SANDBOX = 'sandbox';
    case PREPARED_CHARACTERS = 'pre_written';

    public function getLabel(): string
    {
        return match ($this) {
            self::SANDBOX => 'Players can submit their own characters',
            self::PREPARED_CHARACTERS => 'Characters written by organisers',
        };
    }
}
