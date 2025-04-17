<?php

namespace App\Repository;

use App\Entity\Larp;
use App\Entity\LarpFaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
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

    public function findByOrCreate(string $name, Larp $larp): LarpFaction
    {
        $qb = $this->createQueryBuilder('f');
        $qb->join('f.larps', 'l')
            ->where('f.name = :name')
            ->andWhere('l = :larp')
            ->setParameters(new ArrayCollection(array(
                new Parameter('name', $name),
                new Parameter('larp', $larp)
            )))
            ->setMaxResults(1);

        $faction = $qb->getQuery()->getOneOrNullResult();

        if (!$faction) {
            $faction = new LarpFaction();
            $faction->setName($name);
            $faction->addLarp($larp);
            $this->getEntityManager()->persist($faction);
        }

        return $faction;
    }
}
