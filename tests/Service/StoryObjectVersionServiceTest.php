<?php

namespace App\Tests\Service;

use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\StoryObjectLogEntry;
use App\Domain\StoryObject\Service\StoryObjectVersionService;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use PHPUnit\Framework\TestCase;

class StoryObjectVersionServiceTest extends TestCase
{
    public function testVersionHistoryDiff(): void
    {
        $character = new Character();

        $e1 = new StoryObjectLogEntry();
        $ref = new \ReflectionClass($e1);
        $dataProp = $ref->getProperty('data');
        $dataProp->setAccessible(true);
        $dataProp->setValue($e1, ['title' => 'Hero']);

        $e2 = new StoryObjectLogEntry();
        $dataProp->setValue($e2, ['title' => 'Hero Updated']);

        $repo = $this->createMock(LogEntryRepository::class);
        $repo->expects($this->once())->method('getLogEntries')->with($character)->willReturn([$e2, $e1]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(StoryObjectLogEntry::class)->willReturn($repo);

        $service = new StoryObjectVersionService($em);
        $history = $service->getVersionHistory($character);

        $this->assertCount(2, $history);
        $this->assertSame('Hero Updated', $history[0]['entry']->getData()['title']);
        $this->assertSame('Hero', $history[1]['entry']->getData()['title']);
        $this->assertSame(['old' => 'Hero', 'new' => 'Hero Updated'], $history[0]['diff']['title']);
    }
}
