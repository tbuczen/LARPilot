<?php

namespace App\Repository;

use App\Entity\LarpFaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LarpFaction>
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
