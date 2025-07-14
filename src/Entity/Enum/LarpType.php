<?php

namespace App\Entity\Enum;

enum LarpType: string
{
    case BATTLE = 'battle';
    case STORY = 'story';
    case MIXED = 'mixed';


    public function getLabel(): string
    {
        return match ($this) {
            self::BATTLE => 'Battle',
            self::STORY => 'Story',
            self::MIXED => 'Mixed Story with Battle',
        };
    }
}
