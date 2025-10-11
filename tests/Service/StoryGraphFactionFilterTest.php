<?php

namespace App\Tests\Service;

use App\Entity\StoryObject\Character;
use App\Entity\StoryObject\Faction;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\Thread;
use App\Service\Larp\StoryObjectRelationExplorer;
use App\Service\StoryObject\Graph\GraphEdgeBuilder;
use App\Service\StoryObject\Graph\GraphNodeBuilder;
use PHPUnit\Framework\TestCase;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;

class StoryGraphFactionFilterTest extends TestCase
{
    public function testFactionFilterIncludesConnectedNodes(): void
    {
        $faction = new Faction();
        $character = new Character();
        $thread = new Thread();
        $quest = new Quest();

        $faction->addMember($character);
        $character->addFaction($faction);

        $thread->addInvolvedCharacter($character);
        $character->addThread($thread);

        $quest->setThread($thread);
        $thread->getQuests()->add($quest);
        $quest->addInvolvedCharacter($character);
        $character->addQuest($quest);

        // Use actual instances since classes are readonly and can't be mocked
        $nodeBuilder = new GraphNodeBuilder();
        $edgeBuilder = new GraphEdgeBuilder();

        $explorer = new StoryObjectRelationExplorer($nodeBuilder, $edgeBuilder);
        $graph = $explorer->getGraphFromResults([
            $faction,
            $character,
            $thread,
            $quest,
        ]);

        $nodeIds = array_map(static fn (array $n) => $n['data']['id'], $graph['nodes']);

        $this->assertContains($faction->getId()->toRfc4122(), $nodeIds);
        $this->assertContains($character->getId()->toRfc4122(), $nodeIds);
        $this->assertContains($thread->getId()->toRfc4122(), $nodeIds);
        $this->assertContains($quest->getId()->toRfc4122(), $nodeIds);
    }
}
