<?php

namespace App\Domain\StoryObject\Entity\Enum;

enum RelationType: string
{
    case Friend = 'friend';
    case Enemy = 'enemy';
    case Family = 'family';
    case Ally = 'ally';
}
