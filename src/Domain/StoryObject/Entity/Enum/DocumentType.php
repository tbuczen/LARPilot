<?php

namespace App\Domain\StoryObject\Entity\Enum;

enum DocumentType: string
{
    case LARP = 'larp';           // LARP-wide knowledge base (world-building, lore, rules)
    case CHARACTER = 'character';  // Owned by a character (backstory, personal notes)
    case FACTION = 'faction';      // Owned by a faction (faction history, strategy)
    case LORE = 'lore';      // Lore of the world
    case OTHER = 'other';      // Other unspecified
}
