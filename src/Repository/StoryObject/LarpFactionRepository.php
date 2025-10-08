<?php

namespace App\Repository\StoryObject;

use App\Entity\Larp;
use App\Entity\StoryObject\LarpFaction;
use App\Repository\BaseRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

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

    public function findByOrCreate(string $title, string $larpId): LarpFaction
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
            $faction = new LarpFaction();
            $faction->setTitle($title);
            $faction->setLarp($this->getEntityManager()->getReference(Larp::class, Uuid::fromString($larpId)));
            $this->getEntityManager()->persist($faction);
        }

        return $faction;
    }
}
