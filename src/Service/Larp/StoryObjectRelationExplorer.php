<?php

namespace App\Service\Larp;

use App\Entity\StoryObject\Event;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\StoryObject;
use App\Entity\StoryObject\Thread;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class StoryObjectRelationExplorer
{
    public function getGraphFromResults(iterable $objects): array
    {
        $nodes = [];
        $edges = [];
        $seenEdges = [];

        /** @var StoryObject[] $objects */
        foreach ($objects as $object) {
            $id = 's_' . $object->getId();

            $nodes[$id] = [
                'data' => [
                    'id' => $id,
                    'label' => $object->getTitle(),
                    'type' => $object->getTargetType()->value,
                ]
            ];
        }

        foreach ($objects as $object) {
            $sourceId = 's_' . $object->getId();
            $related = $this->getRelatedStoryObjects($object);

            foreach ($related as $target) {
                $targetId = 's_' . $target->getId();

                // unikaj duplikatów (dwukierunkowe relacje)
                $edgeKeyParts = [$sourceId, $targetId];
                sort($edgeKeyParts);
                $edgeKey = implode('__', $edgeKeyParts);
                if (isset($seenEdges[$edgeKey]) || !isset($nodes[$targetId])) {
                    continue;
                }

                $edges[] = [
                    'data' => [
                        'source' => $sourceId,
                        'target' => $targetId,
                        'type' => 'related',
                    ]
                ];
                $seenEdges[$edgeKey] = true;
            }
        }

        return [
            'nodes' => array_values($nodes),
            'edges' => $edges,
        ];
    }

    public function getRelatedStoryObjects(StoryObject $object): Collection
    {
        $related = new ArrayCollection();

        foreach ($object->getRelationsFrom() as $relation) {
            if ($relation->getTo()) {
                $related->add($relation->getTo());
            }
        }

        foreach ($object->getRelationsTo() as $relation) {
            if ($relation->getFrom()) {
                $related->add($relation->getFrom());
            }
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