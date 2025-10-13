<?php

namespace App\Domain\StoryObject\Service;

use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Entity\Thread;

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
        if ($object instanceof \App\Domain\StoryObject\Entity\Character) {
            $faction = $object->getFactions()->first();
            if ($faction instanceof Faction) {
                $this->factionGroups[$parentId] = $faction;
            }
        }

        // If we have a quest or event, find its thread
        if ($object instanceof \App\Domain\StoryObject\Entity\Quest || $object instanceof \App\Domain\StoryObject\Entity\Event) {
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
