<?php

namespace App\Repository\StoryObject;

use App\Entity\Larp;
use App\Entity\StoryObject\Character;
use App\Entity\StoryObject\StoryObject;
use App\Repository\BaseRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Character>
 *
 * @method null|Character find($id, $lockMode = null, $lockVersion = null)
 * @method null|Character findOneBy(array $criteria, array $orderBy = null)
 * @method Character[]    findAll()
 * @method Character[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CharacterRepository extends BaseRepository implements ListableRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Character::class);
    }

    public function decorateLarpListQueryBuilder(QueryBuilder $qb, Larp $larp): QueryBuilder
    {
        return $qb
            ->innerJoin('c.larp', 'l')
            ->innerJoin(StoryObject::class, 's', 'WITH', 'c.id = s.id')
            ->andWhere('c.larp = :larp')
            ->setParameter('larp', $larp);
    }
}
