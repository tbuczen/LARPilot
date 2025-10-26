<?php

namespace App\Domain\StoryObject\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Core\Repository\ListableRepositoryInterface;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\StoryObject;
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


    public function createCharactersNeedingThreadsQueryBuilder(Larp $larp, int $minThreads): QueryBuilder
    {
        $em = $this->getEntityManager();
        $subQb = $em->createQueryBuilder();
        $subQb->select('c2.id')
            ->from(Character::class, 'c2')
            ->leftJoin('c2.threads', 't2')
            ->where('c2.larp = :larp')
            ->groupBy('c2.id')
            ->having('COUNT(t2.id) < :minThreads');

        $qb = $this->createQueryBuilder('c');
        $qb->innerJoin(StoryObject::class, 's', 'WITH', 'c.id = s.id')
            ->where($qb->expr()->in('c.id', $subQb->getDQL()))
            ->setParameter('larp', $larp)
            ->setParameter('minThreads', $minThreads)
            ->orderBy('c.title', 'ASC');

        return $qb;
    }

    /**
     * Create a query builder for characters filtered by tags.
     *
     * @param Tag[] $tags
     */
    public function createCharactersByTagsQueryBuilder(Larp $larp, array $tags, bool $onlyNeedingThreads = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');
        $qb->join('c.tags', 'tag')
            ->where('c.larp = :larp')
            ->andWhere('tag IN (:tags)')
            ->setParameter('larp', $larp)
            ->setParameter('tags', $tags);

        if ($onlyNeedingThreads) {
            $minThreads = $larp->getMinThreadsPerCharacter();
            $qb->leftJoin('c.threads', 't')
                ->groupBy('c.id')
                ->having('COUNT(t.id) < :minThreads')
                ->setParameter('minThreads', $minThreads);
        }

        $qb->orderBy('c.title', 'ASC');

        return $qb;
    }
}
