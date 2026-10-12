<?php

namespace App\Domain\Integrations\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalSource;
use Doctrine\Persistence\ManagerRegistry;

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
}
