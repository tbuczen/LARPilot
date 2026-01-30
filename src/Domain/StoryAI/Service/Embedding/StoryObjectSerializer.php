<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Service\Embedding;

use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Event;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Entity\Item;
use App\Domain\StoryObject\Entity\Place;
use App\Domain\StoryObject\Entity\Quest;
use App\Domain\StoryObject\Entity\Relation;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Entity\Thread;

/**
 * Serializes story objects to rich text for embedding generation.
 */
class StoryObjectSerializer
{
    /**
     * Serialize a story object to rich text suitable for embedding.
     */
    public function serialize(StoryObject $storyObject): string
    {
        return match (true) {
            $storyObject instanceof Character => $this->serializeCharacter($storyObject),
            $storyObject instanceof Thread => $this->serializeThread($storyObject),
            $storyObject instanceof Quest => $this->serializeQuest($storyObject),
            $storyObject instanceof Faction => $this->serializeFaction($storyObject),
            $storyObject instanceof Event => $this->serializeEvent($storyObject),
            $storyObject instanceof Place => $this->serializePlace($storyObject),
            $storyObject instanceof Item => $this->serializeItem($storyObject),
            $storyObject instanceof Relation => $this->serializeRelation($storyObject),
            default => $this->serializeGeneric($storyObject),
        };
    }

    private function serializeCharacter(Character $character): string
    {
        $parts = [];

        // Header
        $parts[] = sprintf('Character: %s', $character->getTitle());
        if ($character->getInGameName()) {
            $parts[] = sprintf('In-game name: %s', $character->getInGameName());
        }

        // Type and gender
        if ($character->getCharacterType()) {
            $parts[] = sprintf('Type: %s', $character->getCharacterType()->value);
        }
        if ($character->getGender()) {
            $parts[] = sprintf('Gender: %s', $character->getGender()->value);
        }

        // Description
        if ($character->getDescription()) {
            $parts[] = sprintf('Description: %s', $this->cleanHtml($character->getDescription()));
        }

        // Notes (internal story notes)
        if ($character->getNotes()) {
            $parts[] = sprintf('Story notes: %s', $this->cleanHtml($character->getNotes()));
        }

        // Factions
        $factions = $character->getFactions();
        if (!$factions->isEmpty()) {
            $factionNames = [];
            foreach ($factions as $faction) {
                $factionNames[] = $faction->getTitle();
            }
            $parts[] = sprintf('Factions: %s', implode(', ', $factionNames));
        }

        // Threads
        $threads = $character->getThreads();
        if (!$threads->isEmpty()) {
            $threadNames = [];
            foreach ($threads as $thread) {
                $threadNames[] = $thread->getTitle();
            }
            $parts[] = sprintf('Threads: %s', implode(', ', $threadNames));
        }

        // Quests
        $quests = $character->getQuests();
        if (!$quests->isEmpty()) {
            $questNames = [];
            foreach ($quests as $quest) {
                $questNames[] = $quest->getTitle();
            }
            $parts[] = sprintf('Quests: %s', implode(', ', $questNames));
        }

        // Skills
        $skills = $character->getSkills();
        if (!$skills->isEmpty()) {
            $skillNames = [];
            foreach ($skills as $characterSkill) {
                $skill = $characterSkill->getSkill();
                if ($skill) {
                    $skillNames[] = $skill->getName();
                }
            }
            if (!empty($skillNames)) {
                $parts[] = sprintf('Skills: %s', implode(', ', $skillNames));
            }
        }

        // Items
        $items = $character->getItems();
        if (!$items->isEmpty()) {
            $itemNames = [];
            foreach ($items as $characterItem) {
                $item = $characterItem->getItem();
                if ($item) {
                    $itemNames[] = $item->getTitle();
                }
            }
            if (!empty($itemNames)) {
                $parts[] = sprintf('Items: %s', implode(', ', $itemNames));
            }
        }

        // Relations
        $relationsFrom = $character->getRelationsFrom();
        $relationsTo = $character->getRelationsTo();
        $relationStrings = [];

        foreach ($relationsFrom as $relation) {
            $target = $relation->getTo();
            $type = $relation->getRelationType()?->value ?? 'Related';
            if ($target) {
                $relationStrings[] = sprintf('%s of %s', $type, $target->getTitle());
            }
        }

        foreach ($relationsTo as $relation) {
            $source = $relation->getFrom();
            $type = $relation->getRelationType()?->value ?? 'Related';
            if ($source) {
                $relationStrings[] = sprintf('%s with %s', $type, $source->getTitle());
            }
        }

        if (!empty($relationStrings)) {
            $parts[] = sprintf('Relations: %s', implode('; ', $relationStrings));
        }

        // Tags
        $tags = $character->getTags();
        if (!$tags->isEmpty()) {
            $tagNames = [];
            foreach ($tags as $tag) {
                $tagNames[] = $tag->getTitle();
            }
            $parts[] = sprintf('Tags: %s', implode(', ', $tagNames));
        }

        // Recruitment status
        if ($character->isAvailableForRecruitment()) {
            $parts[] = 'Status: Available for recruitment';
        }

        return implode("\n", $parts);
    }

    private function serializeThread(Thread $thread): string
    {
        $parts = [];

        $parts[] = sprintf('Thread: %s', $thread->getTitle());

        if ($thread->getDescription()) {
            $parts[] = sprintf('Description: %s', $this->cleanHtml($thread->getDescription()));
        }

        // Involved characters
        $characters = $thread->getInvolvedCharacters();
        if (!$characters->isEmpty()) {
            $names = [];
            foreach ($characters as $character) {
                $names[] = $character->getTitle();
            }
            $parts[] = sprintf('Involved characters: %s', implode(', ', $names));
        }

        // Involved factions
        $factions = $thread->getInvolvedFactions();
        if (!$factions->isEmpty()) {
            $names = [];
            foreach ($factions as $faction) {
                $names[] = $faction->getTitle();
            }
            $parts[] = sprintf('Involved factions: %s', implode(', ', $names));
        }

        // Related quests
        $quests = $thread->getQuests();
        if (!$quests->isEmpty()) {
            $names = [];
            foreach ($quests as $quest) {
                $names[] = $quest->getTitle();
            }
            $parts[] = sprintf('Quests: %s', implode(', ', $names));
        }

        // Decision tree summary
        $decisionTree = $thread->getDecisionTree();
        if ($decisionTree) {
            $summary = $this->summarizeDecisionTree($decisionTree);
            if ($summary) {
                $parts[] = sprintf('Decision tree: %s', $summary);
            }
        }

        // Tags
        $tags = $thread->getTags();
        if (!$tags->isEmpty()) {
            $tagNames = [];
            foreach ($tags as $tag) {
                $tagNames[] = $tag->getTitle();
            }
            $parts[] = sprintf('Tags: %s', implode(', ', $tagNames));
        }

        return implode("\n", $parts);
    }

    private function serializeQuest(Quest $quest): string
    {
        $parts = [];

        $parts[] = sprintf('Quest: %s', $quest->getTitle());

        if ($quest->getDescription()) {
            $parts[] = sprintf('Description: %s', $this->cleanHtml($quest->getDescription()));
        }

        // Parent thread
        $thread = $quest->getThread();
        if ($thread) {
            $parts[] = sprintf('Part of thread: %s', $thread->getTitle());
        }

        // Involved characters
        $characters = $quest->getInvolvedCharacters();
        if (!$characters->isEmpty()) {
            $names = [];
            foreach ($characters as $character) {
                $names[] = $character->getTitle();
            }
            $parts[] = sprintf('Involved characters: %s', implode(', ', $names));
        }

        // Involved factions
        $factions = $quest->getInvolvedFactions();
        if (!$factions->isEmpty()) {
            $names = [];
            foreach ($factions as $faction) {
                $names[] = $faction->getTitle();
            }
            $parts[] = sprintf('Involved factions: %s', implode(', ', $names));
        }

        // Decision tree summary
        $decisionTree = $quest->getDecisionTree();
        if ($decisionTree) {
            $summary = $this->summarizeDecisionTree($decisionTree);
            if ($summary) {
                $parts[] = sprintf('Decision tree: %s', $summary);
            }
        }

        // Tags
        $tags = $quest->getTags();
        if (!$tags->isEmpty()) {
            $tagNames = [];
            foreach ($tags as $tag) {
                $tagNames[] = $tag->getTitle();
            }
            $parts[] = sprintf('Tags: %s', implode(', ', $tagNames));
        }

        return implode("\n", $parts);
    }

    private function serializeFaction(Faction $faction): string
    {
        $parts = [];

        $parts[] = sprintf('Faction: %s', $faction->getTitle());

        if ($faction->getDescription()) {
            $parts[] = sprintf('Description: %s', $this->cleanHtml($faction->getDescription()));
        }

        // Members
        $members = $faction->getMembers();
        if (!$members->isEmpty()) {
            $names = [];
            foreach ($members as $member) {
                $names[] = $member->getTitle();
            }
            $parts[] = sprintf('Members: %s', implode(', ', $names));
        }

        // Related threads
        $threads = $faction->getThreads();
        if (!$threads->isEmpty()) {
            $names = [];
            foreach ($threads as $thread) {
                $names[] = $thread->getTitle();
            }
            $parts[] = sprintf('Threads: %s', implode(', ', $names));
        }

        // Related quests
        $quests = $faction->getQuests();
        if (!$quests->isEmpty()) {
            $names = [];
            foreach ($quests as $quest) {
                $names[] = $quest->getTitle();
            }
            $parts[] = sprintf('Quests: %s', implode(', ', $names));
        }

        return implode("\n", $parts);
    }

    private function serializeEvent(Event $event): string
    {
        $parts = [];

        $parts[] = sprintf('Event: %s', $event->getTitle());

        if ($event->getDescription()) {
            $parts[] = sprintf('Description: %s', $this->cleanHtml($event->getDescription()));
        }

        return implode("\n", $parts);
    }

    private function serializePlace(Place $place): string
    {
        $parts = [];

        $parts[] = sprintf('Place: %s', $place->getTitle());

        if ($place->getDescription()) {
            $parts[] = sprintf('Description: %s', $this->cleanHtml($place->getDescription()));
        }

        return implode("\n", $parts);
    }

    private function serializeItem(Item $item): string
    {
        $parts = [];

        $parts[] = sprintf('Item: %s', $item->getTitle());

        if ($item->getDescription()) {
            $parts[] = sprintf('Description: %s', $this->cleanHtml($item->getDescription()));
        }

        return implode("\n", $parts);
    }

    private function serializeRelation(Relation $relation): string
    {
        $parts = [];

        $from = $relation->getFrom();
        $to = $relation->getTo();
        $type = $relation->getRelationType()?->getLabel() ?? 'Related';

        if ($from && $to) {
            $parts[] = sprintf(
                'Relation: %s is %s to %s',
                $from->getTitle(),
                $type,
                $to->getTitle()
            );
        } else {
            $parts[] = sprintf('Relation: %s', $relation->getTitle());
        }

        if ($relation->getDescription()) {
            $parts[] = sprintf('Description: %s', $this->cleanHtml($relation->getDescription()));
        }

        return implode("\n", $parts);
    }

    private function serializeGeneric(StoryObject $storyObject): string
    {
        $parts = [];

        $parts[] = sprintf('Story Object: %s', $storyObject->getTitle());

        if ($storyObject->getDescription()) {
            $parts[] = sprintf('Description: %s', $this->cleanHtml($storyObject->getDescription()));
        }

        return implode("\n", $parts);
    }

    /**
     * Clean HTML content for plain text embedding.
     */
    private function cleanHtml(string $html): string
    {
        // Decode HTML entities
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove HTML tags
        $text = strip_tags($text);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Summarize a decision tree for embedding.
     *
     * @param array<mixed>|null $decisionTree
     */
    private function summarizeDecisionTree(?array $decisionTree): ?string
    {
        if (!$decisionTree || empty($decisionTree)) {
            return null;
        }

        $nodes = $decisionTree['nodes'] ?? [];
        if (empty($nodes)) {
            return null;
        }

        $summaryParts = [];
        foreach ($nodes as $node) {
            $label = $node['data']['label'] ?? null;
            $type = $node['type'] ?? 'unknown';

            if ($label) {
                $summaryParts[] = sprintf('%s (%s)', $label, $type);
            }
        }

        if (empty($summaryParts)) {
            return null;
        }

        return implode(' -> ', array_slice($summaryParts, 0, 10));
    }
}
