<?php

namespace App\Domain\StoryObject\Service;

readonly class StoryObjectRelationExplorer
{
    public function __construct(
        private GraphNodeBuilder $nodeBuilder,
        private GraphEdgeBuilder $edgeBuilder,
    ) {
    }

    public function getGraphFromResults(iterable $objects): array
    {
        // Handle grouped results from repository
        if (is_array($objects) && isset($objects['threads'], $objects['characters'], $objects['factions'])) {
            $objects = array_merge(
                $objects['threads'],
                $objects['characters'],
                $objects['factions'],
                $objects['quests'] ?? []
            );
        } elseif (!is_array($objects)) {
            $objects = [...$objects];
        }
        
        $graphData = $this->nodeBuilder->buildNodes($objects);
        $edges = $this->edgeBuilder->buildEdges($objects, $graphData['validNodeIds']);
        
        return [
            'nodes' => $graphData['nodes'],
            'edges' => $edges,
        ];
    }
}
