<?php

namespace App\Domain\StoryObject\Service;

use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Repository\RelationRepository;
use function App\Service\StoryObject\Graph\sort;

readonly class GraphEdgeBuilder
{
    public function __construct(
        private RelationRepository      $relationRepository,
        private ImplicitRelationBuilder $implicitRelationBuilder,
    ) {
    }

    public function buildEdges(array $objects, array $validNodeIds): array
    {
        $edges = [];
        $seenEdges = [];

        $this->addExplicitRelations($objects, $validNodeIds, $edges, $seenEdges);
        $this->addImplicitRelations($objects, $validNodeIds, $edges, $seenEdges);

        return $edges;
    }

    private function addExplicitRelations(array $objects, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        $objectIds = $this->extractObjectIds($objects);
        $relations = $this->relationRepository->findRelationsBetweenObjects($objectIds);

        foreach ($relations as $relation) {
            $sourceId = $relation->getFrom()->getId()->toRfc4122();
            $targetId = $relation->getTo()->getId()->toRfc4122();

            $this->addEdge($sourceId, $targetId, 'relation', $relation->getTitle(), $validNodeIds, $edges, $seenEdges);
        }
    }

    private function addImplicitRelations(array $objects, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        foreach ($objects as $object) {
            $this->implicitRelationBuilder->addImplicitEdges($object, $validNodeIds, $edges, $seenEdges);
        }
    }

    private function extractObjectIds(array $objects): array
    {
        return array_map(
            fn (StoryObject $object): string => $object->getId()->toRfc4122(),
            $objects
        );
    }

    private function addEdge(string $sourceId, string $targetId, string $type, ?string $title, array $validNodeIds, array &$edges, array &$seenEdges): void
    {
        if (!isset($validNodeIds[$sourceId]) || !isset($validNodeIds[$targetId])) {
            return;
        }

        $edgeKey = $this->createEdgeKey($sourceId, $targetId);

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

    private function createEdgeKey(string $sourceId, string $targetId): string
    {
        $parts = [$sourceId, $targetId];
        sort($parts);
        return implode('__', $parts);
    }
}
