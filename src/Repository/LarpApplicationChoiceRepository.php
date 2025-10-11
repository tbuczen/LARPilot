<?php

namespace App\Repository;

use App\Entity\LarpApplicationChoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LarpApplicationChoice>
 *
 * @method null|LarpApplicationChoice find($id, $lockMode = null, $lockVersion = null)
 * @method null|LarpApplicationChoice findOneBy(array $criteria, array $orderBy = null)
 * @method LarpApplicationChoice[]    findAll()
 * @method LarpApplicationChoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LarpApplicationChoiceRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpApplicationChoice::class);
    }
}
