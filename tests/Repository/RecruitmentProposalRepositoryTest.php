<?php

namespace App\Tests\Repository;

use App\Domain\StoryMarketplace\Entity\RecruitmentProposal;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class RecruitmentProposalRepositoryTest extends TestCase
{
    public function testSave(): void
    {
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
