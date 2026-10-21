<?php

namespace Tests\Unit\Core\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Event\LarpDeletedEvent;
use App\Domain\Core\Event\LarpDeletingEvent;
use App\Domain\Core\Service\LarpDeletionService;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Service\IntegrationRegistry;
use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class LarpDeletionServiceTest extends Unit
{
    public function testFailingCleanupAfterCommitDoesNotFailTheDeletion(): void
    {
        $executedStatements = [];
        $connection = $this->createMock(Connection::class);
        $connection->method('transactional')->willReturnCallback(static fn (\Closure $work) => $work($connection));
        $connection->method('executeStatement')->willReturnCallback(static function (string $statement) use (&$executedStatements): int {
            $executedStatements[] = $statement;
            return 1;
        });

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->expects($this->once())->method('clear');

        $integrationRepository = $this->createMock(LarpIntegrationRepository::class);
        $integrationRepository->method('findBy')->willReturn([]);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(LarpDeletingEvent::class, static function (LarpDeletingEvent $deletingEvent): void {
            $deletingEvent->removeAfterDeletion('/not/writable/recording');
        });
        $eventDispatcher->addListener(LarpDeletedEvent::class, static function (): void {
            throw new \RuntimeException('Vector store unavailable');
        });

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('remove')->with(['/not/writable/recording'])->willThrowException(new IOException('Permission denied'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))->method('error');

        $larpDeletionService = new LarpDeletionService(
            $entityManager,
            $integrationRepository,
            $this->createMock(IntegrationRegistry::class),
            $eventDispatcher,
            $logger,
            '/project',
            $filesystem,
        );

        $larpDeletionService->delete(new Larp());

        $this->assertSame('DELETE FROM larp WHERE id = :larp', end($executedStatements));
    }
}
