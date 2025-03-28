<?php

namespace App\Repository;

use App\Entity\LarpFaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LarpFaction>
 *
 * @method null|LarpFaction find($id, $lockMode = null, $lockVersion = null)
 * @method null|LarpFaction findOneBy(array $criteria, array $orderBy = null)
 * @method LarpFaction[]    findAll()
 * @method LarpFaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LarpFactionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpFaction::class);
    }

    public function findByOrCreate(array $array): LarpFaction
    {
        $faction = $this->findOneBy($array);
        if (!$faction) {
            $faction = new LarpFaction();
            $this->getEntityManager()->persist($faction);
        }

        return $faction;
    }
}
