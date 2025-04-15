<?php

namespace App\Enum;

use App\Form\Integrations\CharacterListColumnMappingType;
use App\Form\Integrations\EventListColumnMappingType;
use Symfony\Component\Form\AbstractType;

enum FileMappingType: string
{
    case CHARACTER_LIST = 'character_list';
    case EVENT_LIST = 'event_list';


    /**
     * @return class-string<AbstractType>
     */
    public function getSubForm(): string
    {
        return match ($this) {
            self::CHARACTER_LIST => CharacterListColumnMappingType::class,
            self::EVENT_LIST => EventListColumnMappingType::class,
        };
    }
}
