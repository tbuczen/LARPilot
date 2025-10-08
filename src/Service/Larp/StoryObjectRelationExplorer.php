<?php

namespace App\Service\Larp;

use App\Service\StoryObject\Graph\GraphEdgeBuilder;
use App\Service\StoryObject\Graph\GraphNodeBuilder;

readonly class StoryObjectRelationExplorer
{
    public function __construct(
        private GraphNodeBuilder $nodeBuilder,
        private GraphEdgeBuilder $edgeBuilder,
    ) {
    }

    public function getGraphFromResults(iterable $objects): array
    {
        $objects = is_array($objects) ? $objects : [...$objects];
        
        $graphData = $this->nodeBuilder->buildNodes($objects);
        $edges = $this->edgeBuilder->buildEdges($objects, $graphData['validNodeIds']);
        
        return [
            'nodes' => $graphData['nodes'],
            'edges' => $edges,
        ];
    }
}
