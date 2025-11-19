<?php

declare(strict_types=1);

namespace Tests\Integration\StoryMarketplace;

use App\Domain\StoryMarketplace\Entity\RecruitmentProposal;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tests\Support\FunctionalTester;

class RecruitmentProposalRepositoryCest extends Unit
{
    public function saveMethodPersistsAndFlushes(FunctionalTester $I): void
    {
        $I->wantTo('verify that save() method persists and flushes the entity');

        $entity = new RecruitmentProposal();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($entity);
        $em->expects($this->once())->method('flush');

        $registry = $this->createMock(ManagerRegistry::class);
        $repository = new class($registry, $em) extends \App\Domain\StoryMarketplace\Repository\RecruitmentProposalRepository {
            public function __construct(ManagerRegistry $registry, private readonly EntityManagerInterface $em)
            {
                parent::__construct($registry);
            }

            protected function getEntityManager(): EntityManagerInterface
            {
                return $this->em;
            }
        };

        $repository->save($entity);
    }
}
