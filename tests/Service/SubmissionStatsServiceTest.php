<?php

namespace App\Tests\Service;

use App\Domain\Application\Entity\LarpApplication;
use App\Domain\Application\Entity\LarpApplicationChoice;
use App\Domain\Application\Repository\LarpApplicationRepository;
use App\Domain\Core\Entity\Larp;
use App\Domain\Larp\Service\SubmissionStatsService;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Faction;
use PHPUnit\Framework\TestCase;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;

class SubmissionStatsServiceTest extends TestCase
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

        $preloader = $this->createMock(EntityPreloader::class);
        $preloader->expects($this->exactly(3))
            ->method('preload')
            ->withConsecutive(
                [$this->identicalTo([$application]), 'choices'],
                [$this->identicalTo([$application]), 'choices.character'],
                [$this->identicalTo($factionsArray), 'members'],
            );

        $service = new SubmissionStatsService($repo, $preloader);
        $stats = $service->getStatsForLarp($larp);

        $this->assertSame([$application], $stats['applications']);
        $this->assertCount(1, $stats['factionStats']);
        $this->assertSame($faction, $stats['factionStats'][0]['faction']);
        $this->assertSame(100.0, $stats['factionStats'][0]['percentage']);
    }
}
