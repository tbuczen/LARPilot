<?php

namespace App\Service\StoryObject\Graph;

use App\Entity\StoryObject\Event;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\StoryObject;
use App\Entity\StoryObject\Thread;

readonly class ImplicitRelationBuilder
{
    public function addImplicitEdges(StoryObject $object, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        $sourceId = $object->getId()->toRfc4122();

        match (true) {
            $object instanceof LarpCharacter => $this->addCharacterEdges($object, $sourceId, $validNodeIds, $edges, $seenEdges),
            $object instanceof LarpFaction => $this->addFactionEdges($object, $sourceId, $validNodeIds, $edges, $seenEdges),
            $object instanceof Thread => $this->addThreadEdges($object, $sourceId, $validNodeIds, $edges, $seenEdges),
            $object instanceof Quest => $this->addQuestEdges($object, $sourceId, $validNodeIds, $edges, $seenEdges),
            $object instanceof Event => $this->addEventEdges($object, $sourceId, $validNodeIds, $edges, $seenEdges),
            default => null,
        };
    }

    private function addCharacterEdges(LarpCharacter $character, string $sourceId, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        // Character -> Faction (only if faction is standalone node)
        foreach ($character->getFactions() as $faction) {
            $factionId = $faction->getId()->toRfc4122();
            if (isset($validNodeIds[$factionId])) {
                $this->addEdgeIfValid($sourceId, $factionId, 'membership', null, $validNodeIds, $edges, $seenEdges);
            }
        }

        // Character -> Thread involvement (direct edges to thread nodes)
        foreach ($character->getThreads() as $thread) {
            $this->addEdgeIfValid($sourceId, $thread->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
        }

        // Character -> Quest involvement (direct edges to quest nodes)
        foreach ($character->getQuests() as $quest) {
            $this->addEdgeIfValid($sourceId, $quest->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
        }
    }

    private function addFactionEdges(LarpFaction $faction, string $sourceId, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        // Faction -> Thread involvement (direct edges to thread nodes)
        foreach ($faction->getThreads() as $thread) {
            $this->addEdgeIfValid($sourceId, $thread->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
        }

        // Faction -> Quest involvement (direct edges to quest nodes)
        foreach ($faction->getQuests() as $quest) {
            $this->addEdgeIfValid($sourceId, $quest->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
        }
    }

    private function addThreadEdges(Thread $thread, string $sourceId, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        // Thread -> Involved Characters (for standalone threads)
        foreach ($thread->getInvolvedCharacters() as $character) {
            $this->addEdgeIfValid($sourceId, $character->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
        }

        // Thread -> Involved Factions (for standalone threads)
//        foreach ($thread->getInvolvedFactions() as $faction) {
//            $this->addEdgeIfValid($sourceId, $faction->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
//        }

        // Note: When thread acts as a group, quests/events are children, not connected by edges
        // When thread is standalone, it can still have involvement edges but no quest/event children
    }

    private function addQuestEdges(Quest $quest, string $sourceId, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        // Quest -> Involved Characters (direct edges)
        foreach ($quest->getInvolvedCharacters() as $character) {
            $this->addEdgeIfValid($sourceId, $character->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
        }

        // Quest -> Involved Factions (direct edges)
        foreach ($quest->getInvolvedFactions() as $faction) {
            $this->addEdgeIfValid($sourceId, $faction->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
        }

        // Quest -> Thread (only if thread is standalone node, not acting as parent group)
        $thread = $quest->getThread();
        if ($thread) {
            $threadId = $thread->getId()->toRfc4122();
            // Check if thread is standalone (no group created for it)
            $threadGroupId = $thread->getId()->toBase32();
            if (isset($validNodeIds[$threadId]) && !isset($validNodeIds[$threadGroupId])) {
                $this->addEdgeIfValid($sourceId, $threadId, 'belongs_to', null, $validNodeIds, $edges, $seenEdges);
            }
        }
    }

    private function addEventEdges(Event $event, string $sourceId, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        // Event -> Involved Characters (direct edges)
        foreach ($event->getInvolvedCharacters() as $character) {
            $this->addEdgeIfValid($sourceId, $character->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
        }

        // Event -> Involved Factions (direct edges)
        foreach ($event->getInvolvedFactions() as $faction) {
            $this->addEdgeIfValid($sourceId, $faction->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
        }

        // Event -> Thread (only if thread is standalone node, not acting as parent group)
        $thread = $event->getThread();
        if ($thread) {
            $threadId = $thread->getId()->toRfc4122();
            // Check if thread is standalone (no group created for it)
            $threadGroupId = $thread->getId()->toBase32();
            if (isset($validNodeIds[$threadId]) && !isset($validNodeIds[$threadGroupId])) {
                $this->addEdgeIfValid($sourceId, $threadId, 'belongs_to', null, $validNodeIds, $edges, $seenEdges);
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
}