<?php

namespace App\Entity\Enum;

enum TargetType: string
{
    case Character = 'character'; //can be a player, short NPC, long NPC, GM, someone who acts in the larp
    case Faction = 'faction'; // a group of players, NPCs, GMs, etc. that are part of the larp

    case Thread = 'thread'; // collective name for quests/event that are related to each other

    case Event = 'event'; //one time event held by organizers - for everyone/specific players/factions
    case Quest = 'quest'; //quest/objective between players/factions that might include GM and events
    case Relation = 'relation'; // describes relation between players/factions, can be anything starting from friendship, family to rivalry

    //Both storyline -> threads -> events and quests can have a decision tree

    //Example
    //At larp Skyrim there is a thread "Leader health issues",
    // it will have event "Dining with leader" that will accommodate few factions in one place by invitation of the leader
    // there will be a quest to poison the leader, that will be available to few factions/players attending the event
    // the quest will have a decision tree, where players can choose to poison the leader or not
    // there will be other quest for leader's faction to find to heal him which may lead to discovering that he has been poisoned/ cursed
    // some characters might know some bits of information
    case Item = 'item';

    public function getEntityClass(): string
    {
        return match ($this) {
            self::Character => \App\Entity\StoryObject\LarpCharacter::class,
            self::Thread    => \App\Entity\StoryObject\Thread::class,
            self::Quest     => \App\Entity\StoryObject\Quest::class,
            self::Event     => \App\Entity\StoryObject\Event::class,
            self::Relation  => \App\Entity\StoryObject\Relation::class,
            self::Faction   => \App\Entity\StoryObject\LarpFaction::class,
            self::Item      => \App\Entity\StoryObject\Item::class,
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
        ];
    }
}
