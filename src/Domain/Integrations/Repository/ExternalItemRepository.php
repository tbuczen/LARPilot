<?php

namespace App\Domain\Integrations\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends BaseRepository<ExternalItem>
 *
 * @method null|ExternalItem find($id, $lockMode = null, $lockVersion = null)
 * @method null|ExternalItem findOneBy(array $criteria, array $orderBy = null)
 * @method ExternalItem[]    findAll()
 * @method ExternalItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExternalItemRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalItem::class);
    }

    public function findOneByExternalId(ExternalSource $source, string $externalId): ?ExternalItem
    {
        return $this->findOneBy(['source' => $source, 'externalId' => $externalId]);
    }

    /**
     * @param list<string> $externalIds
     * @return array<string, ExternalItem>
     */
    public function findBySourceIndexedByExternalId(ExternalSource $source, array $externalIds): array
    {
        if ($externalIds === []) {
            return [];
        }

        $items = $this->createQueryBuilder('item')
            ->andWhere('item.source = :source')
            ->andWhere('item.externalId IN (:externalIds)')
            ->setParameter('source', $source)
            ->setParameter('externalIds', $externalIds)
            ->getQuery()
            ->getResult();

        $itemsByExternalId = [];
        foreach ($items as $item) {
            $itemsByExternalId[$item->getExternalId()] = $item;
        }

        return $itemsByExternalId;
    }

    /**
     * @return array<string, ExternalItem>
     */
    public function findForSourceIndexedByExternalId(ExternalSource $source): array
    {
        $itemsByExternalId = [];
        foreach ($this->findBy(['source' => $source]) as $item) {
            $itemsByExternalId[$item->getExternalId()] = $item;
        }

        return $itemsByExternalId;
    }

    /**
     * @return ExternalItem[]
     */
    public function searchForLarp(Larp $larp, string $searchTerm, int $limit = 20): array
    {
        return $this->search($this->createLarpQueryBuilder($larp), $searchTerm, $limit);
    }

    /**
     * @return ExternalItem[]
     */
    public function searchForIntegration(LarpIntegration $integration, string $searchTerm, int $limit = 50): array
    {
        $queryBuilder = $this->createLarpQueryBuilder($integration->getLarp())
            ->andWhere('source.integration = :integration')
            ->setParameter('integration', $integration);

        return $this->search($queryBuilder, $searchTerm, $limit);
    }

    public function findOneForLarp(Larp $larp, string $itemId): ?ExternalItem
    {
        if (!Uuid::isValid($itemId)) {
            return null;
        }

        return $this->createLarpQueryBuilder($larp)
            ->andWhere('item.id = :itemId')
            ->setParameter('itemId', Uuid::fromString($itemId), 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneForLarpByExternalId(Larp $larp, LarpIntegrationProvider $provider, string $externalId): ?ExternalItem
    {
        return $this->createLarpQueryBuilder($larp)
            ->andWhere('integration.provider = :provider')
            ->andWhere('item.externalId = :externalId')
            ->setParameter('provider', $provider)
            ->setParameter('externalId', $externalId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return ExternalItem[]
     */
    private function search(QueryBuilder $queryBuilder, string $searchTerm, int $limit): array
    {
        $queryBuilder
            ->orderBy('item.externalUpdatedAt', 'DESC')
            ->addOrderBy('item.id', 'ASC')
            ->setMaxResults($limit);

        $normalizedTerm = trim($searchTerm);
        if ($normalizedTerm === '') {
            return $queryBuilder->getQuery()->getResult();
        }

        $queryBuilder
            ->andWhere('LOWER(item.title) LIKE :searchTerm OR LOWER(item.excerpt) LIKE :searchTerm OR LOWER(source.title) LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . addcslashes(mb_strtolower($normalizedTerm), '%_\\') . '%');

        return $queryBuilder->getQuery()->getResult();
    }

    private function createLarpQueryBuilder(Larp $larp): QueryBuilder
    {
        return $this->createQueryBuilder('item')
            ->addSelect('source', 'integration')
            ->innerJoin('item.source', 'source')
            ->innerJoin('source.integration', 'integration')
            ->andWhere('integration.larp = :larp')
            ->setParameter('larp', $larp);
    }
}
