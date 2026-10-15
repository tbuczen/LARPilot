<?php

namespace App\Domain\Integrations\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

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

    public function findMiroBoardOfLarp(string $sourceId, Larp $larp): ?ExternalSource
    {
        if (!Uuid::isValid($sourceId)) {
            return null;
        }

        $board = $this->find($sourceId);
        if (!$board instanceof ExternalSource || $board->getKind() !== ExternalSourceKind::MiroBoard) {
            return null;
        }

        $integration = $board->getIntegration();
        if ($integration->getProvider() !== LarpIntegrationProvider::Miro || !$integration->getLarp()->getId()->equals($larp->getId())) {
            return null;
        }

        return $board;
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
    public function findEnabledForLarpByKind(Larp $larp, ExternalSourceKind $kind): array
    {
        return $this->createQueryBuilder('source')
            ->addSelect('integration')
            ->innerJoin('source.integration', 'integration')
            ->andWhere('integration.larp = :larp')
            ->andWhere('source.kind = :kind')
            ->andWhere('source.enabled = true')
            ->setParameter('larp', $larp)
            ->setParameter('kind', $kind)
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

    public function deleteForIntegration(LarpIntegration $integration): int
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete(ExternalItem::class, 'item')
            ->andWhere('item.source IN (SELECT source.id FROM ' . ExternalSource::class . ' source WHERE source.integration = :integration)')
            ->setParameter('integration', $integration)
            ->getQuery()
            ->execute();

        return (int) $this->createQueryBuilder('source')
            ->delete()
            ->andWhere('source.integration = :integration')
            ->setParameter('integration', $integration)
            ->getQuery()
            ->execute();
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
