<?php

namespace App\Repository;

use App\Entity\ObjectFieldMapping;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ObjectFieldMapping>
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
