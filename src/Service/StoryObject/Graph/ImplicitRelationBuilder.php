<?php

namespace App\Service\StoryObject\Graph;

use App\Entity\StoryObject\Event;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\StoryObject;

readonly class ImplicitRelationBuilder
{
    public function addImplicitEdges(StoryObject $object, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        $sourceId = $object->getId()->toRfc4122();

        match (true) {
            $object instanceof LarpCharacter => $this->addCharacterEdges($object, $sourceId, $validNodeIds, $edges, $seenEdges),
            $object instanceof LarpFaction => $this->addFactionEdges($object, $sourceId, $validNodeIds, $edges, $seenEdges),
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

        // Character -> Thread/Quest involvement
        foreach ($character->getThreads() as $thread) {
            $this->addEdgeIfValid($sourceId, $thread->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
        }

        foreach ($character->getQuests() as $quest) {
            $this->addEdgeIfValid($sourceId, $quest->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
        }
    }

    private function addFactionEdges(LarpFaction $faction, string $sourceId, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        foreach ($faction->getThreads() as $thread) {
            $this->addEdgeIfValid($sourceId, $thread->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
        }

        foreach ($faction->getQuests() as $quest) {
            $this->addEdgeIfValid($sourceId, $quest->getId()->toRfc4122(), 'involvement', null, $validNodeIds, $edges, $seenEdges);
        }
    }

    private function addQuestEdges(Quest $quest, string $sourceId, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        $thread = $quest->getThread();
        if ($thread) {
            $threadId = $thread->getId()->toRfc4122();
            if (isset($validNodeIds[$threadId])) {
                $this->addEdgeIfValid($sourceId, $threadId, 'contains', null, $validNodeIds, $edges, $seenEdges);
            }
        }
    }

    private function addEventEdges(Event $event, string $sourceId, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        $thread = $event->getThread();
        if ($thread) {
            $threadId = $thread->getId()->toRfc4122();
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
}