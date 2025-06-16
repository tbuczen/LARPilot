<?php

namespace App\Repository;

use App\Entity\LarpApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LarpApplication>
 *
 * @method null|LarpApplication find($id, $lockMode = null, $lockVersion = null)
 * @method null|LarpApplication findOneBy(array $criteria, array $orderBy = null)
 * @method LarpApplication[]    findAll()
 * @method LarpApplication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LarpApplicationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpApplication::class);
    }
}
