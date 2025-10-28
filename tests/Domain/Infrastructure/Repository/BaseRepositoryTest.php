<?php

namespace App\Tests\Domain\Infrastructure\Repository;

use App\Domain\Core\Repository\BaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class BaseRepositoryTest extends TestCase
{
    public function testSavePersistsAndFlushes(): void
    {
        $entity = new \stdClass();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($entity);
        $em->expects($this->once())->method('flush');

        $repository = new class($em) extends BaseRepository {
            public function __construct(private readonly EntityManagerInterface $em)
            {
            }
            protected function getEntityManager(): EntityManagerInterface
            {
                return $this->em;
            }
        };

        $repository->save($entity);
    }

    public function testRemoveDeletesAndFlushes(): void
    {
        $entity = new \stdClass();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($entity);
        $em->expects($this->once())->method('flush');

        $repository = new class($em) extends BaseRepository {
            public function __construct(private readonly EntityManagerInterface $em)
            {
            }
            protected function getEntityManager(): EntityManagerInterface
            {
                return $this->em;
            }
        };

        $repository->remove($entity);
    }
}
