<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Service;

use App\Domain\StoryObject\DTO\MentionDTO;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Event;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Entity\Item;
use App\Domain\StoryObject\Entity\Place;
use App\Domain\StoryObject\Entity\Quest;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Entity\Thread;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to find all mentions of a StoryObject across other StoryObjects.
 * Follows SOLID principles with single responsibility for mention detection.
 */
class StoryObjectMentionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Find all mentions of the given StoryObject.
     *
     * @return MentionDTO[]
     */
    public function findMentions(StoryObject $storyObject): array
    {
        $mentions = [];

        // Find relation-based mentions
        $mentions = array_merge($mentions, $this->findRelationMentions($storyObject));

        // Find field-level mentions based on the type of story object
        $mentions = array_merge($mentions, $this->findFieldMentions($storyObject));

        return $mentions;
    }

    /**
     * Find mentions through the Relation entity.
     *
     * @return MentionDTO[]
     */
    private function findRelationMentions(StoryObject $storyObject): array
    {
        $mentions = [];
        $relationsTo = $storyObject->getRelationsTo();

        foreach ($relationsTo as $relation) {
            $sourceObject = $relation->getFrom();
            $relationType = $relation->getRelationType();

            $mentions[] = new MentionDTO(
                sourceObject: $sourceObject,
                mentionType: 'story_object.mention.type.relation',
                context: $relationType ? sprintf('Relation: %s', $relationType) : 'Relation',
                fieldName: 'relation',
            );
        }

        return $mentions;
    }

    /**
     * Find mentions through entity field references.
     *
     * @return MentionDTO[]
     */
    private function findFieldMentions(StoryObject $storyObject): array
    {
        $mentions = [];

        if ($storyObject instanceof Character) {
            $mentions = array_merge($mentions, $this->findCharacterMentions($storyObject));
        } elseif ($storyObject instanceof Faction) {
            $mentions = array_merge($mentions, $this->findFactionMentions($storyObject));
        } elseif ($storyObject instanceof Quest) {
            $mentions = array_merge($mentions, $this->findQuestMentions($storyObject));
        } elseif ($storyObject instanceof Thread) {
            $mentions = array_merge($mentions, $this->findThreadMentions($storyObject));
        } elseif ($storyObject instanceof Event) {
            $mentions = array_merge($mentions, $this->findEventMentions($storyObject));
        } elseif ($storyObject instanceof Item) {
            $mentions = array_merge($mentions, $this->findItemMentions($storyObject));
        } elseif ($storyObject instanceof Place) {
            $mentions = array_merge($mentions, $this->findPlaceMentions($storyObject));
        }

        return $mentions;
    }

    /**
     * @return MentionDTO[]
     */
    private function findCharacterMentions(Character $character): array
    {
        $mentions = [];

        // Mentioned in Faction.members
        foreach ($character->getFactions() as $faction) {
            $mentions[] = new MentionDTO(
                sourceObject: $faction,
                mentionType: 'story_object.mention.type.faction_member',
                context: 'Member of faction',
                fieldName: 'members',
            );
        }

        // Mentioned in Quest.involvedCharacters
        foreach ($character->getQuests() as $quest) {
            $mentions[] = new MentionDTO(
                sourceObject: $quest,
                mentionType: 'story_object.mention.type.involved_in_quest',
                context: 'Involved in quest',
                fieldName: 'involvedCharacters',
            );
        }

        // Mentioned in Thread.involvedCharacters
        foreach ($character->getThreads() as $thread) {
            $mentions[] = new MentionDTO(
                sourceObject: $thread,
                mentionType: 'story_object.mention.type.involved_in_thread',
                context: 'Involved in thread',
                fieldName: 'involvedCharacters',
            );
        }

        // Mentioned in Character.previousCharacter (as continuation)
        if ($character->getContinuation() !== null) {
            $mentions[] = new MentionDTO(
                sourceObject: $character->getContinuation(),
                mentionType: 'story_object.mention.type.character_continuation',
                context: 'Continued by character',
                fieldName: 'previousCharacter',
            );
        }

        // Mentioned in Character.continuation (as previous character)
        if ($character->getPreviousCharacter() !== null) {
            $mentions[] = new MentionDTO(
                sourceObject: $character,
                mentionType: 'story_object.mention.type.character_previous',
                context: 'Continues from character',
                fieldName: 'continuation',
            );
        }

        // Mentioned in Event.involvedCharacters
        $events = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->join('e.involvedCharacters', 'ic')
            ->where('ic = :character')
            ->setParameter('character', $character)
            ->getQuery()
            ->getResult();
        foreach ($events as $event) {
            $mentions[] = new MentionDTO(
                sourceObject: $event,
                mentionType: 'story_object.mention.type.involved_in_event',
                context: 'Involved in event',
                fieldName: 'involvedCharacters',
            );
        }

        return $mentions;
    }

    /**
     * @return MentionDTO[]
     */
    private function findFactionMentions(Faction $faction): array
    {
        $mentions = [];

        // Mentioned in Character.factions
        foreach ($faction->getMembers() as $character) {
            $mentions[] = new MentionDTO(
                sourceObject: $character,
                mentionType: 'story_object.mention.type.character_member',
                context: 'Has character member',
                fieldName: 'factions',
            );
        }

        // Mentioned in Quest.involvedFactions
        foreach ($faction->getQuests() as $quest) {
            $mentions[] = new MentionDTO(
                sourceObject: $quest,
                mentionType: 'story_object.mention.type.involved_in_quest',
                context: 'Involved in quest',
                fieldName: 'involvedFactions',
            );
        }

        // Mentioned in Thread.involvedFactions
        foreach ($faction->getThreads() as $thread) {
            $mentions[] = new MentionDTO(
                sourceObject: $thread,
                mentionType: 'story_object.mention.type.involved_in_thread',
                context: 'Involved in thread',
                fieldName: 'involvedFactions',
            );
        }

        // Mentioned in Event.involvedFactions
        $events = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->join('e.involvedFactions', 'if')
            ->where('if = :faction')
            ->setParameter('faction', $faction)
            ->getQuery()
            ->getResult();
        foreach ($events as $event) {
            $mentions[] = new MentionDTO(
                sourceObject: $event,
                mentionType: 'story_object.mention.type.involved_in_event',
                context: 'Involved in event',
                fieldName: 'involvedFactions',
            );
        }

        return $mentions;
    }

    /**
     * @return MentionDTO[]
     */
    private function findQuestMentions(Quest $quest): array
    {
        $mentions = [];

        // Mentioned in Character.quests
        foreach ($quest->getInvolvedCharacters() as $character) {
            $mentions[] = new MentionDTO(
                sourceObject: $character,
                mentionType: 'story_object.mention.type.character_quest',
                context: 'Character has quest',
                fieldName: 'quests',
            );
        }

        // Mentioned in Faction.quests
        foreach ($quest->getInvolvedFactions() as $faction) {
            $mentions[] = new MentionDTO(
                sourceObject: $faction,
                mentionType: 'story_object.mention.type.faction_quest',
                context: 'Faction has quest',
                fieldName: 'quests',
            );
        }

        // Mentioned in Thread.quests
        if ($quest->getThread() !== null) {
            $mentions[] = new MentionDTO(
                sourceObject: $quest->getThread(),
                mentionType: 'story_object.mention.type.thread_quest',
                context: 'Part of thread',
                fieldName: 'quests',
            );
        }

        return $mentions;
    }

    /**
     * @return MentionDTO[]
     */
    private function findThreadMentions(Thread $thread): array
    {
        $mentions = [];

        // Mentioned in Character.threads
        foreach ($thread->getInvolvedCharacters() as $character) {
            $mentions[] = new MentionDTO(
                sourceObject: $character,
                mentionType: 'story_object.mention.type.character_thread',
                context: 'Character has thread',
                fieldName: 'threads',
            );
        }

        // Mentioned in Faction.threads
        foreach ($thread->getInvolvedFactions() as $faction) {
            $mentions[] = new MentionDTO(
                sourceObject: $faction,
                mentionType: 'story_object.mention.type.faction_thread',
                context: 'Faction has thread',
                fieldName: 'threads',
            );
        }

        // Mentioned in Quest.thread
        foreach ($thread->getQuests() as $quest) {
            $mentions[] = new MentionDTO(
                sourceObject: $quest,
                mentionType: 'story_object.mention.type.quest_thread',
                context: 'Quest belongs to thread',
                fieldName: 'thread',
            );
        }

        // Mentioned in Event.thread
        foreach ($thread->getEvents() as $event) {
            $mentions[] = new MentionDTO(
                sourceObject: $event,
                mentionType: 'story_object.mention.type.event_thread',
                context: 'Event belongs to thread',
                fieldName: 'thread',
            );
        }

        return $mentions;
    }

    /**
     * @return MentionDTO[]
     */
    private function findEventMentions(Event $event): array
    {
        $mentions = [];

        // Mentioned in Thread.events
        if ($event->getThread() !== null) {
            $mentions[] = new MentionDTO(
                sourceObject: $event->getThread(),
                mentionType: 'story_object.mention.type.thread_event',
                context: 'Part of thread',
                fieldName: 'events',
            );
        }

        return $mentions;
    }

    /**
     * @return MentionDTO[]
     */
    private function findItemMentions(Item $item): array
    {
        $mentions = [];

        // Mentioned in Character.items
        $characters = $this->entityManager->getRepository(Character::class)
            ->createQueryBuilder('c')
            ->join('c.items', 'i')
            ->where('i = :item')
            ->setParameter('item', $item)
            ->getQuery()
            ->getResult();

        foreach ($characters as $character) {
            $mentions[] = new MentionDTO(
                sourceObject: $character,
                mentionType: 'story_object.mention.type.character_item',
                context: 'Character owns item',
                fieldName: 'items',
            );
        }

        return $mentions;
    }

    /**
     * @return MentionDTO[]
     */
    private function findPlaceMentions(Place $place): array
    {
        $mentions = [];

        // Mentioned in Event.place
        $events = $this->entityManager->getRepository(Event::class)->findBy([
            'place' => $place,
        ]);

        foreach ($events as $event) {
            $mentions[] = new MentionDTO(
                sourceObject: $event,
                mentionType: 'story_object.mention.type.event_place',
                context: 'Event takes place here',
                fieldName: 'place',
            );
        }

        return $mentions;
    }
}
