<?php

namespace App\Domain\Integrations\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<ObjectFieldMapping>
 *
 * @method null|ObjectFieldMapping find($id, $lockMode = null, $lockVersion = null)
 * @method null|ObjectFieldMapping findOneBy(array $criteria, array $orderBy = null)
 * @method ObjectFieldMapping[]    findAll()
 * @method ObjectFieldMapping[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ObjectFieldMappingRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ObjectFieldMapping::class);
    }
}
