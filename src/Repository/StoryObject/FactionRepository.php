<?php

namespace App\Repository\StoryObject;

use App\Entity\Larp;
use App\Entity\StoryObject\Faction;
use App\Repository\BaseRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Faction>
 *
 * @method null|Faction find($id, $lockMode = null, $lockVersion = null)
 * @method null|Faction findOneBy(array $criteria, array $orderBy = null)
 * @method Faction[]    findAll()
 * @method Faction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FactionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Faction::class);
    }

    public function findByOrCreate(string $title, string $larpId): Faction
    {
        $qb = $this->createQueryBuilder('f');
        $qb->where('f.title = :title')
            ->andWhere('f.larp = :larp')
            ->setParameters(new ArrayCollection([
                new Parameter('title', $title),
                new Parameter('larp', Uuid::fromString($larpId))
            ]))
            ->setMaxResults(1);

        $faction = $qb->getQuery()->getOneOrNullResult();

        if (!$faction) {
            $faction = new Faction();
            $faction->setTitle($title);
            $faction->setLarp($this->getEntityManager()->getReference(Larp::class, Uuid::fromString($larpId)));
            $this->getEntityManager()->persist($faction);
        }

        return $faction;
    }
}
