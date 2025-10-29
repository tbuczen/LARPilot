<?php

namespace App\Domain\StoryObject\Entity\Enum;

enum EventCategory: string
{
    case Historical = 'historical'; // Past events in the lore/world history
    case Current = 'current'; // Events happening during the LARP
    case Future = 'future'; // Planned/prophesied events in the story
}
