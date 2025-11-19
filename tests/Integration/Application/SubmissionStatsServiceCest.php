<?php

declare(strict_types=1);

namespace Tests\Integration\Application;

use App\Domain\Application\Entity\LarpApplication;
use App\Domain\Application\Entity\LarpApplicationChoice;
use App\Domain\Application\Repository\LarpApplicationRepository;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Service\SubmissionStatsService;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Faction;
use Codeception\Test\Unit;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;
use Tests\Support\FunctionalTester;

class SubmissionStatsServiceCest extends Unit
{
    public function statsCalculatedCorrectlyForLarp(FunctionalTester $I): void
    {
        $I->wantTo('verify that submission stats are calculated correctly for a LARP');

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

        $preloader = $this->createMock(EntityPreloader::class);
        $preloader->expects($this->exactly(2))
            ->method('preload')
            ->willReturnCallback(function ($entities, $property) use ($application, $factionsArray) {
                // First call: preload choices for applications
                // Second call: preload members for factions
                return null;
            });

        $service = new SubmissionStatsService($repo, $preloader);
        $stats = $service->getStatsForLarp($larp);

        $I->assertSame([$application], $stats['applications']);
        $I->assertCount(1, $stats['factionStats']);
        $I->assertSame($faction, $stats['factionStats'][0]['faction']);
        $I->assertEquals(100.0, $stats['factionStats'][0]['percentage']);
    }
}
