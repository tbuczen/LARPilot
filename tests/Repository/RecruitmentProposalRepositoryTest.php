<?php

namespace App\Tests\Repository;

use App\Entity\StoryObject\RecruitmentProposal;
use App\Repository\StoryObject\RecruitmentProposalRepository;
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
        $repository = new class($registry, $em) extends RecruitmentProposalRepository {
            public function __construct(ManagerRegistry $registry, private EntityManagerInterface $em)
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
