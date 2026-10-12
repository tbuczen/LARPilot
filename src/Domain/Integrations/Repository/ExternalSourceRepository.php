<?php

namespace App\Domain\Integrations\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<ExternalSource>
 *
 * @method null|ExternalSource find($id, $lockMode = null, $lockVersion = null)
 * @method null|ExternalSource findOneBy(array $criteria, array $orderBy = null)
 * @method ExternalSource[]    findAll()
 * @method ExternalSource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExternalSourceRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalSource::class);
    }

    public function findOneByExternalId(LarpIntegration $integration, string $externalId): ?ExternalSource
    {
        return $this->findOneBy(['integration' => $integration, 'externalId' => $externalId]);
    }

    /**
     * @return ExternalSource[]
     */
    public function findForLarp(Larp $larp): array
    {
        return $this->createQueryBuilder('source')
            ->addSelect('integration')
            ->innerJoin('source.integration', 'integration')
            ->andWhere('integration.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('source.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ExternalSource[]
     */
    public function findForIntegration(LarpIntegration $integration): array
    {
        return $this->createQueryBuilder('source')
            ->andWhere('source.integration = :integration')
            ->setParameter('integration', $integration)
            ->orderBy('source.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
