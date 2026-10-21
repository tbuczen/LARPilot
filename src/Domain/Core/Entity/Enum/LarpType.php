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
            self::BATTLE => 'enum.larp_type.battle',
            self::STORY => 'enum.larp_type.story',
            self::MIXED => 'enum.larp_type.mixed',
        };
    }
}
