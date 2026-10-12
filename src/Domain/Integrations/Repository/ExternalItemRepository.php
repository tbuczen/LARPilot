<?php

namespace App\Domain\Integrations\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalSource;
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
     * @return ExternalItem[]
     */
    public function searchForLarp(Larp $larp, string $searchTerm, int $limit = 20): array
    {
        $queryBuilder = $this->createLarpQueryBuilder($larp)
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
