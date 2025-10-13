<?php

namespace App\Domain\StoryObject\Entity\Enum;

enum StoryTimeUnit: string
{
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';
    case Era = 'era';
}
