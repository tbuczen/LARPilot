<?php

namespace App\Domain\Integrations\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<LarpIntegration>
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
