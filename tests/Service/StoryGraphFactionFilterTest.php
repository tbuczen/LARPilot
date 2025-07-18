<?php

namespace App\Tests\Service;

use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\Thread;
use App\Service\Larp\StoryObjectRelationExplorer;
use PHPUnit\Framework\TestCase;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;

class StoryGraphFactionFilterTest extends TestCase
{
    public function testFactionFilterIncludesConnectedNodes(): void
    {
        $faction = new LarpFaction();
        $character = new LarpCharacter();
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

        $preloader = $this->createMock(EntityPreloader::class);
        $preloader->method('preload')->willReturn([]);

        $explorer = new StoryObjectRelationExplorer($preloader);
        $graph = $explorer->getGraphFromResults([
            $faction,
            $character,
            $thread,
            $quest,
        ]);

        $nodeIds = array_map(static fn ($n) => $n['data']['id'], $graph['nodes']);

        $this->assertContains($faction->getId()->toRfc4122(), $nodeIds);
        $this->assertContains($character->getId()->toRfc4122(), $nodeIds);
        $this->assertContains($thread->getId()->toRfc4122(), $nodeIds);
        $this->assertContains($quest->getId()->toRfc4122(), $nodeIds);
    }
}
