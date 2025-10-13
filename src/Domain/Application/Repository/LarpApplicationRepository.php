<?php

namespace App\Domain\Application\Repository;

use App\Domain\Application\Entity\LarpApplication;
use App\Domain\Core\Repository\BaseRepository;
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
