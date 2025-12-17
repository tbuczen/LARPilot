<?php

namespace Tests\Unit\Application\Service;

use App\Domain\Application\Entity\LarpApplication;
use App\Domain\Application\Entity\LarpApplicationChoice;
use App\Domain\Application\Repository\LarpApplicationRepository;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Service\SubmissionStatsService;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Faction;
use Codeception\Test\Unit;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;

class SubmissionStatsServiceTest extends Unit
{
    public function testGetStatsForLarp(): void
    {
        $larp = new Larp();

        $faction = new Faction();
        $larp->addFaction($faction);

        $character = new Character();
        $larp->addCharacter($character);
        $faction->addMember($character);

        $application = new LarpApplication();
        $application->setLarp($larp);
        $choice = new LarpApplicationChoice();
        $choice->setCharacter($character);
        $application->addChoice($choice);

        $repo = $this->createMock(LarpApplicationRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['larp' => $larp])
            ->willReturn([$application]);

        $factionsArray = $larp->getFactions()->toArray();

        // Track preload calls since withConsecutive() was removed in PHPUnit 10
        $preloadCalls = [];
        $preloader = $this->createMock(EntityPreloader::class);
        $preloader->expects($this->exactly(2))
            ->method('preload')
            ->willReturnCallback(function ($entities, $relation) use (&$preloadCalls, $application, $factionsArray) {
                $preloadCalls[] = ['entities' => $entities, 'relation' => $relation];

                // Verify the calls match expected arguments
                if (count($preloadCalls) === 1) {
                    $this->assertSame([$application], $entities);
                    $this->assertSame('choices', $relation);
                } elseif (count($preloadCalls) === 2) {
                    $this->assertSame($factionsArray, $entities);
                    $this->assertSame('members', $relation);
                }

                return $entities;
            });

        $service = new SubmissionStatsService($repo, $preloader);
        $stats = $service->getStatsForLarp($larp);

        $this->assertSame([$application], $stats['applications']);
        $this->assertCount(1, $stats['factionStats']);
        $this->assertSame($faction, $stats['factionStats'][0]['faction']);
        $this->assertSame(100.0, $stats['factionStats'][0]['percentage']);
    }
}
