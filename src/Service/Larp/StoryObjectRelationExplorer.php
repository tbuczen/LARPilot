<?php

namespace App\Service\Larp;

use App\Entity\StoryObject\Event;
use App\Entity\StoryObject\Item;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\Relation;
use App\Entity\StoryObject\StoryObject;
use App\Entity\StoryObject\Thread;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;

readonly class StoryObjectRelationExplorer
{

    public function __construct(
        private EntityPreloader $preloader,
    ) {
    }

    public function getGraphFromResults(iterable $objects): array
    {
        $objects = is_array($objects) ? $objects : [...$objects];
        $this->preloadRelations($objects);

        $nodes = [];
        $edges = [];
        $seenEdges = [];

        $parentFactionGroups = [];
        $parentThreadGroups = [];

        /** @var StoryObject[] $objects */
        foreach ($objects as $object) {
            if ($object instanceof Relation) {
                continue;
            }

            $id = $object->getId()->toRfc4122();

            $nodes[$id] = [
                'data' => [
                    'id' => $id,
                    'title' => $object->getTitle(),
                    'type' => $object->getTargetType()->value,
                ]
            ];

            //Factions grouping
            if ($object instanceof LarpCharacter) {
                $faction = $object->getFactions()->first();
                if ($faction instanceof LarpFaction) {
                    $nodes[$id]['data']['parent'] = $faction->getId()->toBase32();
                    if(!in_array($faction->getId()->toBase32(), $parentFactionGroups)) {
                        $parentFactionGroups[$faction->getId()->toBase32()] = $faction;
                    }
                }
            }
            if ($object instanceof LarpFaction) {
                $nodes[$id]['data']['parent'] = $object->getId()->toBase32();
                if(!in_array($object->getId()->toBase32(), $parentFactionGroups)) {
                    $parentFactionGroups[$object->getId()->toBase32()] = $object;
                }
            }


            //Thread grouping
            if ($object instanceof Quest || $object instanceof Event) {
                $thread = $object->getThread();
                $nodes[$id]['data']['parent'] = $thread->getId()->toBase32();
                if(!in_array($thread->getId()->toBase32(), $parentThreadGroups)) {
                    $parentThreadGroups[$thread->getId()->toBase32()] = $thread;
                }
            }
            if ($object instanceof Thread) {
                $nodes[$id]['data']['parent'] = $object->getId()->toBase32();
                if(!in_array($object->getId()->toBase32(), $parentThreadGroups)) {
                    $parentThreadGroups[$object->getId()->toBase32()] = $object;
                }
            }
        }

        foreach ($parentThreadGroups as $id => $parentNode) {
            $nodes[$id] = [
                'data' => [
                    'id' => $id,
                    'title' => $parentNode->getTitle(),
                ]
            ];
        }

        foreach ($parentFactionGroups as $id => $parentNode) {
            $nodes[$id] = [
                'data' => [
                    'id' => $id,
                    'title' => $parentNode->getTitle(),
                ]
            ];
        }

        foreach ($objects as $object) {
            $relatedStoryObjects = $this->getRelatedStoryObjects($object);

            foreach ($relatedStoryObjects as $relatedStoryObject) {
                $edgeKeyParts = [$object->getId()->toRfc4122(), $relatedStoryObject->getId()->toRfc4122()];
                sort($edgeKeyParts);
                $edgeKey = implode('__', $edgeKeyParts);
                if (isset($seenEdges[$edgeKey])) {
                    continue;
                }

                if ($relatedStoryObject instanceof Relation) {
                    $edges[] = [
                        'data' => [
                            'source' => $object->getId()->toRfc4122(),
                            'target' => $relatedStoryObject->getTo()->getId()->toRfc4122(),
                            'type' => 'related',
                            'title' => $relatedStoryObject->getTitle(),
                        ]
                    ];
                } else {
                    $edges[] = [
                        'data' => [
                            'source' => $object->getId()->toRfc4122(),
                            'target' => $relatedStoryObject->getId()->toRfc4122(),
                            'type' => 'related',
                            'title' => null,
                        ]
                    ];
                }
                $seenEdges[$edgeKey] = true;
            }
        }

        return [
            'nodes' => array_values($nodes),
            'edges' => $edges,
        ];
    }

    /**
     * @param StoryObject[] $objects
     */
    private function preloadRelations(array $objects): void
    {
        if ($objects === []) {
            return;
        }

        $this->preloader->preload($objects, 'relationsFrom');
        $this->preloader->preload($objects, 'relationsTo');

        $relationsFrom = [];
        $relationsTo = [];
        $characters = [];
        $factions = [];
        $threads = [];
        $quests = [];
        $events = [];

        foreach ($objects as $object) {
            foreach ($object->getRelationsFrom() as $relation) {
                $relationsFrom[] = $relation;
            }
            foreach ($object->getRelationsTo() as $relation) {
                $relationsTo[] = $relation;
            }

            if ($object instanceof LarpCharacter) {
                $characters[] = $object;
            }
            if ($object instanceof LarpFaction) {
                $factions[] = $object;
            }
            if ($object instanceof Thread) {
                $threads[] = $object;
            }
            if ($object instanceof Quest) {
                $quests[] = $object;
            }
            if ($object instanceof Event) {
                $events[] = $object;
            }
        }

        $this->preloader->preload($relationsFrom, 'to');
        $this->preloader->preload($relationsTo, 'from');

        if ($characters !== []) {
            $this->preloader->preload($characters, 'factions');
            $this->preloader->preload($characters, 'quests');
            $this->preloader->preload($characters, 'threads');
        }

        if ($factions !== []) {
            $this->preloader->preload($factions, 'members');
            $this->preloader->preload($factions, 'threads');
            $this->preloader->preload($factions, 'quests');
        }

        if ($threads !== []) {
            $this->preloader->preload($threads, 'quests');
            $this->preloader->preload($threads, 'events');
            $this->preloader->preload($threads, 'involvedCharacters');
            $this->preloader->preload($threads, 'involvedFactions');
        }

        if ($quests !== []) {
            $this->preloader->preload($quests, 'thread');
            $this->preloader->preload($quests, 'involvedCharacters');
            $this->preloader->preload($quests, 'involvedFactions');
        }

        if ($events !== []) {
            $this->preloader->preload($events, 'thread');
            $this->preloader->preload($events, 'involvedCharacters');
            $this->preloader->preload($events, 'involvedFactions');
        }
    }

    /**
     * @param StoryObject $object
     * @return Collection<StoryObject>
     */
    public function getRelatedStoryObjects(StoryObject $object): Collection
    {
        $related = new ArrayCollection();

        foreach ($object->getRelationsFrom() as $relation) {
            $related->add($relation);
        }

        if ($object instanceof LarpCharacter) {
            $this->addFromCollection($object->getFactions(), $related);
            $this->addFromCollection($object->getQuests(), $related);
            $this->addFromCollection($object->getThreads(), $related);
        }

        if ($object instanceof LarpFaction) {
            $this->addFromCollection($object->getMembers(), $related);
            $this->addFromCollection($object->getThreads(), $related);
            $this->addFromCollection($object->getQuests(), $related);
        }

        if ($object instanceof Thread) {
            $this->addFromCollection($object->getQuests(), $related);
            $this->addFromCollection($object->getEvents(), $related);
            $this->addFromCollection($object->getInvolvedCharacters(), $related);
            $this->addFromCollection($object->getInvolvedFactions(), $related);
        }

        if ($object instanceof Quest) {
            if ($object->getThread()) {
                $related->add($object->getThread());
            }
            $this->addFromCollection($object->getInvolvedCharacters(), $related);
            $this->addFromCollection($object->getInvolvedFactions(), $related);
        }

        if ($object instanceof Event) {
            if ($object->getThread()) {
                $related->add($object->getThread());
            }
            $this->addFromCollection($object->getInvolvedCharacters(), $related);
            $this->addFromCollection($object->getInvolvedFactions(), $related);
        }

        return $related;
    }

    private function addFromCollection(Collection $source, Collection $target): void
    {
        foreach ($source as $item) {
            $target->add($item);
        }
    }

}