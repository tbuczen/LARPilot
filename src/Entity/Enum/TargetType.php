<?php

namespace App\Entity\Enum;

enum TargetType: string
{
    case Character = 'character'; //can be a player, short NPC, long NPC, GM, someone who acts in the larp
    case Faction = 'faction'; // a group of players, NPCs, GMs, etc. that are part of the larp

    case Storyline = 'storyline'; // a collection of threads and quest on the timeline of the larp

    case Thread = 'thread'; // collective name for quests that are related to each other

    case Event = 'event'; //one time event held by organizers - for everyone/specific players/factions
    case Quest = 'quest'; //quest/objective between players/factions that might include GM and events
    case Relation = 'relation'; // describes relation between players/factions, can be anything starting from friendship, family to rivalry

    //Both storyline -> threads -> events and quests can have a decision tree
}
