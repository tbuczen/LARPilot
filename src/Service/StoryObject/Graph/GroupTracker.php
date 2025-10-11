<?php

namespace App\Service\StoryObject\Graph;

use App\Entity\StoryObject\Faction;
use App\Entity\StoryObject\StoryObject;
use App\Entity\StoryObject\Thread;

class GroupTracker
{
    private array $factionGroups = [];
    private array $threadGroups = [];

    public function addToGroup(string $parentId, StoryObject $object): void
    {
        if ($object instanceof Faction) {
            $this->factionGroups[$parentId] = $object;
        } elseif ($object instanceof Thread) {
            $this->threadGroups[$parentId] = $object;
        } else {
            // Determine group type by checking existing groups or object relationships
            $this->addToApropriateGroup($parentId, $object);
        }
    }

    private function addToApropriateGroup(string $parentId, StoryObject $object): void
    {
        // If we have a character, find its faction
        if ($object instanceof \App\Entity\StoryObject\Character) {
            $faction = $object->getFactions()->first();
            if ($faction instanceof Faction) {
                $this->factionGroups[$parentId] = $faction;
            }
        }

        // If we have a quest or event, find its thread
        if ($object instanceof \App\Entity\StoryObject\Quest || $object instanceof \App\Entity\StoryObject\Event) {
            $thread = $object->getThread();
            if ($thread instanceof Thread) {
                $this->threadGroups[$parentId] = $thread;
            }
        }
    }

    public function getFactionGroups(): array
    {
        return $this->factionGroups;
    }

    public function getThreadGroups(): array
    {
        return $this->threadGroups;
    }
}
