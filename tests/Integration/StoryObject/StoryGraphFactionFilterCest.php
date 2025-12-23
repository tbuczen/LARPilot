<?php

declare(strict_types=1);

namespace Tests\Integration\StoryObject;

use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Entity\Quest;
use App\Domain\StoryObject\Entity\Thread;
use App\Domain\StoryObject\Service\GraphEdgeBuilder;
use App\Domain\StoryObject\Service\GraphNodeBuilder;
use App\Domain\StoryObject\Service\StoryObjectRelationExplorer;
use Codeception\Test\Unit;
use Tests\Support\FunctionalTester;

class StoryGraphFactionFilterCest extends Unit
{
    public function factionFilterIncludesConnectedNodes(FunctionalTester $I): void
    {
        $I->wantTo('verify that faction filter includes all connected nodes in the graph');

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
        $relationRepository = $this->createMock(\App\Domain\StoryObject\Repository\RelationRepository::class);
        $implicitRelationBuilder = new \App\Domain\StoryObject\Service\ImplicitRelationBuilder();
        $edgeBuilder = new GraphEdgeBuilder($relationRepository, $implicitRelationBuilder);

        $explorer = new StoryObjectRelationExplorer($nodeBuilder, $edgeBuilder);
        $graph = $explorer->getGraphFromResults([
            $faction,
            $character,
            $thread,
            $quest,
        ]);

        $nodeIds = array_map(static fn (array $n) => $n['data']['id'], $graph['nodes']);

        $I->assertContains($faction->getId()->toRfc4122(), $nodeIds);
        $I->assertContains($character->getId()->toRfc4122(), $nodeIds);
        $I->assertContains($thread->getId()->toRfc4122(), $nodeIds);
        $I->assertContains($quest->getId()->toRfc4122(), $nodeIds);
    }
}
