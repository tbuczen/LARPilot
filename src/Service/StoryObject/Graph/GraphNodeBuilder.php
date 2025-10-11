<?php

namespace App\Service\StoryObject\Graph;

use App\Entity\StoryObject\Character;
use App\Entity\StoryObject\Event;
use App\Entity\StoryObject\Faction;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\StoryObject;
use App\Entity\StoryObject\Thread;

readonly class GraphNodeBuilder
{
    public function buildNodes(array $objects): array
    {
        $nodes = [];
        $validNodeIds = [];
        $groupTracker = new GroupTracker();

        // Build object nodes and track grouping
        foreach ($objects as $object) {
            $nodeData = $this->createObjectNode($object);
            $this->addGroupingToNode($object, $nodeData, $groupTracker);

            $id = $object->getId()->toRfc4122();
            $validNodeIds[$id] = true;
            $nodes[] = $nodeData;
        }

        // Add group nodes
        $groupNodes = $this->createGroupNodes($groupTracker);
        foreach ($groupNodes as $groupNode) {
            $validNodeIds[$groupNode['data']['id']] = true;
            $nodes[] = $groupNode;
        }

        return [
            'nodes' => $nodes,
            'validNodeIds' => $validNodeIds,
        ];
    }

    private function createObjectNode(StoryObject $object): array
    {
        return [
            'data' => [
                'id' => $object->getId()->toRfc4122(),
                'title' => $object->getTitle(),
                'type' => $object->getTargetType()->value,
            ]
        ];
    }

    private function addGroupingToNode(StoryObject $object, array &$nodeData, GroupTracker $groupTracker): void
    {
        $parentId = $this->determineParentGroup($object);

        if ($parentId) {
            $nodeData['data']['parent'] = $parentId;
            $groupTracker->addToGroup($parentId, $object);
        }
    }

    private function determineParentGroup(StoryObject $object): ?string
    {
        // Faction grouping
        if ($object instanceof Character) {
            $faction = $object->getFactions()->first();
            if ($faction instanceof Faction) {
                return $faction->getId()->toBase32();
            }
        }

        if ($object instanceof Faction) {
            return $object->getId()->toBase32();
        }

        // Thread grouping - only if thread has quests or events
        if ($object instanceof Quest || $object instanceof Event) {
            $thread = $object->getThread();
            if ($thread instanceof Thread) {
                return $thread->getId()->toBase32();
            }
        }

        if ($object instanceof Thread) {
            // Only create group if thread has quests or events
            if ($object->getQuests()->count() > 0 || $object->getEvents()->count() > 0) {
                return $object->getId()->toBase32();
            }
            // If thread has no quests/events, don't create a group - it will be standalone
        }

        return null;
    }

    private function createGroupNodes(GroupTracker $groupTracker): array
    {
        $nodes = [];

        foreach ($groupTracker->getFactionGroups() as $id => $faction) {
            $nodes[] = [
                'data' => [
                    'id' => $id,
                    'title' => $faction->getTitle(),
                    'type' => 'factionGroup',
                ]
            ];
        }

        foreach ($groupTracker->getThreadGroups() as $id => $thread) {
            $nodes[] = [
                'data' => [
                    'id' => $id,
                    'title' => $thread->getTitle(),
                    'type' => 'threadGroup',
                ]
            ];
        }

        return $nodes;
    }
}
