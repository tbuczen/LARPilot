<?php

namespace App\Domain\Core\Entity\Enum;

enum LarpType: string implements LabelableEnumInterface
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
