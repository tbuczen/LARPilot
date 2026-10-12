<?php

namespace App\Domain\Integrations\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
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
     * @param list<LarpIntegrationProvider> $providers
     * @return list<ExternalSource>
     */
    public function findDueForSync(\DateTimeImmutable $now, array $providers, int $limit): array
    {
        if ($providers === []) {
            return [];
        }

        return $this->createQueryBuilder('source')
            ->addSelect('CASE WHEN source.nextSyncAt IS NULL THEN 0 ELSE 1 END AS HIDDEN neverScheduled')
            ->innerJoin('source.integration', 'integration')
            ->andWhere('source.enabled = true')
            ->andWhere('integration.status = :activeStatus')
            ->andWhere('integration.provider IN (:providers)')
            ->andWhere('source.nextSyncAt IS NULL OR source.nextSyncAt <= :now')
            ->setParameter('activeStatus', IntegrationStatus::Active)
            ->setParameter('providers', array_map(static fn (LarpIntegrationProvider $provider): string => $provider->value, $providers))
            ->setParameter('now', $now)
            ->orderBy('neverScheduled', 'ASC')
            ->addOrderBy('source.nextSyncAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
