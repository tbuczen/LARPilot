<?php

namespace Functional\StoryObject\Service;

use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Entity\Quest;
use App\Domain\StoryObject\Entity\Thread;
use App\Domain\StoryObject\Repository\RelationRepository;
use App\Domain\StoryObject\Service\GraphEdgeBuilder;
use App\Domain\StoryObject\Service\GraphNodeBuilder;
use App\Domain\StoryObject\Service\ImplicitRelationBuilder;
use App\Domain\StoryObject\Service\StoryObjectRelationExplorer;
use PHPUnit\Framework\TestCase;

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
        // GraphEdgeBuilder needs dependencies - ImplicitRelationBuilder is readonly, create real instance
        $relationRepository = $this->createMock(RelationRepository::class);
        $implicitRelationBuilder = new ImplicitRelationBuilder();
        $edgeBuilder = new GraphEdgeBuilder($relationRepository, $implicitRelationBuilder);

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
