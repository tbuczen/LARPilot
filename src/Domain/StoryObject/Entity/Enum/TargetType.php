<?php

namespace App\Domain\StoryObject\Entity\Enum;

use App\Domain\Core\Entity\Tag;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Event;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Entity\Item;
use App\Domain\StoryObject\Entity\Place;
use App\Domain\StoryObject\Entity\Quest;
use App\Domain\StoryObject\Entity\Relation;
use App\Domain\StoryObject\Entity\Thread;

enum TargetType: string
{
    //Example
    //At one Core there is a Thread "Leader health issues",
    // it will have Event "Dining with leader" that will accommodate few Factions in one place by invitation of the leader
    // there will be a Quest to poison the leader in this Thread, that will be available to few factions/players attending the event
    // the Quest will have a decision tree, where players can choose to poison the leader or not
    // there will be other Quest for leader's faction to find to heal him which may lead to discovering that he has been poisoned/ cursed
    // some Characters might know some bits of information
    // some Characters might have some items needed to complete the Quest

    case Character = 'character'; //can be a player, short NPC, long NPC, GM, someone who acts in the larp
    case Faction = 'faction'; // a group of players, NPCs, GMs, etc. that are part of the larp

    case Thread = 'thread'; // collective name for quests/event that are related to each other

    case Event = 'event'; //one time event held by organizers - for everyone/specific players/factions
    case Quest = 'quest'; //quest/objective between players/factions that might include GM and events

    case Item = 'item';

    case Place = 'place'; // a location in the larp world, can be a place of interest, a quest location, etc.
    case Relation = 'relation'; // describes relation between players/factions, can be anything starting from friendship, family to rivalry
    case Tag = 'tag';
    case MapLocation = 'map_location'; // a location on a game map, used for tagging map markers

    //Both storyline -> threads -> events and quests can have a decision tree
    public function getEntityClass(): string
    {
        return match ($this) {
            self::Character => Character::class,
            self::Thread => Thread::class,
            self::Quest => Quest::class,
            self::Event => Event::class,
            self::Relation => Relation::class,
            self::Faction => Faction::class,
            self::Item => Item::class,
            self::Place => Place::class,
            self::Tag => Tag::class,
            // Use FQCN string to avoid cross-domain import
            self::MapLocation => 'App\\Domain\\Map\\Entity\\MapLocation',
        };
    }

    public static function getAvailableForRelations(): array
    {
        return [
            self::Character,
            self::Thread,
            self::Event,
            self::Faction,
            self::Item,
            self::Place,
        ];
    }
}
