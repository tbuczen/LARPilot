<?php

namespace App\Repository\StoryObject;

use App\Entity\Larp;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\StoryObject;
use App\Repository\BaseRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LarpCharacter>
 *
 * @method null|LarpCharacter find($id, $lockMode = null, $lockVersion = null)
 * @method null|LarpCharacter findOneBy(array $criteria, array $orderBy = null)
 * @method LarpCharacter[]    findAll()
 * @method LarpCharacter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LarpCharacterRepository extends BaseRepository implements ListableRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpCharacter::class);
    }

    public function createListQueryBuilder(Larp $larp): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.larp', 'l')
            ->innerJoin(StoryObject::class, 's', 'WITH', 'c.id = s.id')
            ->andWhere('c.larp = :larp')
            ->setParameter('larp', $larp);
    }
}
