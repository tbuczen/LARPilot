<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repository;

use App\Domain\Core\Repository\BaseRepository;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Support\UnitTester;

class BaseRepositoryTest extends Unit
{
    public function savePersistsAndFlushes(UnitTester $I): void
    {
        $I->wantTo('verify that save() persists and flushes the entity');

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

    public function removeDeletesAndFlushes(UnitTester $I): void
    {
        $I->wantTo('verify that remove() deletes and flushes the entity');

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
