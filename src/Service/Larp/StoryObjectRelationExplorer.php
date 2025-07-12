<?php

namespace App\Service\Larp;

use App\Entity\StoryObject\Event;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\StoryObject;
use App\Entity\StoryObject\Thread;
use App\Repository\StoryObject\RelationRepository;
use Doctrine\Common\Collections\Collection;

readonly class StoryObjectRelationExplorer
{
    public function __construct(
        private RelationRepository $relationRepository,
    ) {
    }

    public function getGraphFromResults(iterable $objects): array
    {
        $objects = is_array($objects) ? $objects : [...$objects];
        
        // Create nodes and track grouping
        $nodes = [];
        $validNodeIds = [];
        $parentFactionGroups = [];
        $parentThreadGroups = [];
        
        // Separate tracking for actual objects vs group nodes
        $actualObjectIds = [];
        
        foreach ($objects as $object) {
            $id = $object->getId()->toRfc4122();
            $validNodeIds[$id] = true;
            $actualObjectIds[] = $id; // Track actual object IDs for relations
            
            $nodeData = [
                'data' => [
                    'id' => $id,
                    'title' => $object->getTitle(),
                    'type' => $object->getTargetType()->value,
                ]
            ];
            
            // Faction grouping
            if ($object instanceof LarpCharacter) {
                $faction = $object->getFactions()->first();
                if ($faction instanceof LarpFaction) {
                    $parentId = $faction->getId()->toBase32();
                    $nodeData['data']['parent'] = $parentId;
                    if (!isset($parentFactionGroups[$parentId])) {
                        $parentFactionGroups[$parentId] = $faction;
                    }
                }
            }
            
            if ($object instanceof LarpFaction) {
                $parentId = $object->getId()->toBase32();
                $nodeData['data']['parent'] = $parentId;
                if (!isset($parentFactionGroups[$parentId])) {
                    $parentFactionGroups[$parentId] = $object;
                }
            }
            
            // Thread grouping
            if ($object instanceof Quest || $object instanceof Event) {
                $thread = $object->getThread();
                if ($thread) {
                    $parentId = $thread->getId()->toBase32();
                    $nodeData['data']['parent'] = $parentId;
                    if (!isset($parentThreadGroups[$parentId])) {
                        $parentThreadGroups[$parentId] = $thread;
                    }
                }
            }
            
            if ($object instanceof Thread) {
                $parentId = $object->getId()->toBase32();
                $nodeData['data']['parent'] = $parentId;
                if (!isset($parentThreadGroups[$parentId])) {
                    $parentThreadGroups[$parentId] = $object;
                }
            }
            
            $nodes[] = $nodeData;
        }
        
        // Add parent group nodes
        foreach ($parentFactionGroups as $id => $faction) {
            $validNodeIds[$id] = true;
            $nodes[] = [
                'data' => [
                    'id' => $id,
                    'title' => $faction->getTitle(),
                    'type' => 'factionGroup',
                ]
            ];
        }
        
        foreach ($parentThreadGroups as $id => $thread) {
            $validNodeIds[$id] = true;
            $nodes[] = [
                'data' => [
                    'id' => $id,
                    'title' => $thread->getTitle(),
                    'type' => 'threadGroup',
                ]
            ];
        }
        
        // Create edges
        $edges = [];
        $seenEdges = [];
        
        // 1. Direct relations between actual objects (not group nodes)
        $relations = $this->relationRepository->findRelationsBetweenObjects($actualObjectIds);
        
        foreach ($relations as $relation) {
            $sourceId = $relation->getFrom()->getId()->toRfc4122();
            $targetId = $relation->getTo()->getId()->toRfc4122();
            
            $edgeKey = $sourceId . '__' . $targetId;
            if (!isset($seenEdges[$edgeKey])) {
                $edges[] = [
                    'data' => [
                        'source' => $sourceId,
                        'target' => $targetId,
                        'type' => 'relation',
                        'title' => $relation->getTitle(),
                    ]
                ];
                $seenEdges[$edgeKey] = true;
            }
        }
        
        // 2. Implicit relationships (membership, involvement, etc.)
        foreach ($objects as $object) {
            $this->addImplicitEdges($object, $validNodeIds, $edges, $seenEdges);
        }
        
        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }

    private function addImplicitEdges(StoryObject $object, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        $sourceId = $object->getId()->toRfc4122();
        
        if ($object instanceof LarpCharacter) {
            // Character -> Faction edges (only if faction is not grouped)
            foreach ($object->getFactions() as $faction) {
                $factionId = $faction->getId()->toRfc4122();
                // Only add edge if faction is not acting as a parent group
                if (isset($validNodeIds[$factionId])) {
                    $this->addEdgeIfValid($sourceId, $factionId, 'membership', null, $validNodeIds, $edges, $seenEdges);
                }
            }
            
            // Character -> Thread/Quest/Event edges
            foreach ($object->getThreads() as $thread) {
                $this->addEdgeIfValid($sourceId, $thread->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
            }
            foreach ($object->getQuests() as $quest) {
                $this->addEdgeIfValid($sourceId, $quest->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
            }
        }
        
        if ($object instanceof LarpFaction) {
            // Faction -> Thread/Quest edges
            foreach ($object->getThreads() as $thread) {
                $this->addEdgeIfValid($sourceId, $thread->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
            }
            foreach ($object->getQuests() as $quest) {
                $this->addEdgeIfValid($sourceId, $quest->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
            }
        }
        
        if ($object instanceof Quest && $object->getThread()) {
            $threadId = $object->getThread()->getId()->toRfc4122();
            // Only add edge if thread is not acting as a parent group
            if (isset($validNodeIds[$threadId])) {
                $this->addEdgeIfValid($sourceId, $threadId, 'contains', null, $validNodeIds, $edges, $seenEdges);
            }
        }
        
        if ($object instanceof Event && $object->getThread()) {
            $threadId = $object->getThread()->getId()->toRfc4122();
            // Only add edge if thread is not acting as a parent group
            if (isset($validNodeIds[$threadId])) {
                $this->addEdgeIfValid($sourceId, $threadId, 'contains', null, $validNodeIds, $edges, $seenEdges);
            }
        }
    }

    private function addEdgeIfValid(string $sourceId, string $targetId, string $type, ?string $title, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        if (!isset($validNodeIds[$sourceId]) || !isset($validNodeIds[$targetId])) {
            return;
        }
        
        $edgeKeyParts = [$sourceId, $targetId];
        sort($edgeKeyParts);
        $edgeKey = implode('__', $edgeKeyParts);
        
        if (!isset($seenEdges[$edgeKey])) {
            $edges[] = [
                'data' => [
                    'source' => $sourceId,
                    'target' => $targetId,
                    'type' => $type,
                    'title' => $title,
                ]
            ];
            $seenEdges[$edgeKey] = true;
        }
    }

    private function addFromCollection(Collection $source, Collection $target): void
    {
        foreach ($source as $item) {
            $target->add($item);
        }
    }
}