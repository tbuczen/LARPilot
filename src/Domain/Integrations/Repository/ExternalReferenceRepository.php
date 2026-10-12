<?php

namespace App\Domain\Integrations\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Integrations\Entity\ExternalReference;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<ExternalReference>
 *
 * @method null|ExternalReference find($id, $lockMode = null, $lockVersion = null)
 * @method null|ExternalReference findOneBy(array $criteria, array $orderBy = null)
 * @method ExternalReference[]    findAll()
 * @method ExternalReference[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExternalReferenceRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalReference::class);
    }
}
