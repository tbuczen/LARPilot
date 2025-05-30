<?php

namespace App\Repository;

use App\Entity\Enum\LarpIntegrationProvider;
use App\Entity\Larp;
use App\Entity\LarpIntegration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LarpIntegration>
 *
 * @method null|LarpIntegration find($id, $lockMode = null, $lockVersion = null)
 * @method null|LarpIntegration findOneBy(array $criteria, array $orderBy = null)
 * @method LarpIntegration[]    findAll()
 * @method LarpIntegration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
    public function findAllByLarp(string|Larp $larp): array
    {
        return $this->createQueryBuilder('li')
            ->andWhere('li.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('li.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByLarpAndProvider(string $larpId, LarpIntegrationProvider $provider): LarpIntegration
    {
        return $this->findOneBy([
            'larp' => $larpId,
            'provider' => $provider,
        ]);
    }
}
