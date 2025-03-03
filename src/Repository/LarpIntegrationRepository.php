<?php

namespace App\Repository;

use App\Entity\LarpIntegration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LarpIntegration>
 */
class LarpIntegrationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpIntegration::class);
    }

    /**
     * @return LarpIntegration[]
     */
    public function findAllByLarp(string $larpId): array
    {
        return $this->createQueryBuilder('li')
            ->andWhere('li.larp = :larp')
            ->setParameter('larp', $larpId)
            ->orderBy('li.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
