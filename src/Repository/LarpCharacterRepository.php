<?php

namespace App\Repository;

use App\Entity\LarpCharacter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LarpCharacter>
 *
 * @method null|LarpCharacter find($id, $lockMode = null, $lockVersion = null)
 * @method null|LarpCharacter findOneBy(array $criteria, array $orderBy = null)
 * @method LarpCharacter[]    findAll()
 * @method LarpCharacter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LarpCharacterRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpCharacter::class);
    }

}
