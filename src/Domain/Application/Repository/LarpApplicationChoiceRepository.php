<?php

namespace App\Domain\Application\Repository;

use App\Domain\Application\Entity\LarpApplicationChoice;
use App\Domain\Application\Entity\LarpApplicationVote;
use App\Domain\Core\Repository\BaseRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<\App\Domain\Application\Entity\LarpApplicationChoice>
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

    /**
     * Get all choices with eagerly loaded relationships for matching
     */
    public function findForMatchingWithRelations(QueryBuilder $qb): array
    {
        $qb->select('c')
            ->addSelect('app')
            ->addSelect('char')
            ->addSelect('user')
            ->leftJoin('c.application', 'app')
            ->leftJoin('app.user', 'user')
            ->leftJoin('c.character', 'char');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all votes for given choice IDs in a single query
     *
     * @param string[] $choiceIds
     * @return array Indexed by choice ID
     */
    public function findVotesGroupedByChoice(array $choiceIds): array
    {
        if (empty($choiceIds)) {
            return [];
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $votes = $qb->select('v', 'c', 'u')
            ->from(LarpApplicationVote::class, 'v')
            ->join('v.choice', 'c')
            ->join('v.user', 'u')
            ->where($qb->expr()->in('c.id', ':choiceIds'))
            ->setParameter('choiceIds', $choiceIds)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Group by choice ID
        $grouped = [];
        foreach ($votes as $vote) {
            $choiceId = $vote->getChoice()->getId()->toRfc4122();
            if (!isset($grouped[$choiceId])) {
                $grouped[$choiceId] = [];
            }
            $grouped[$choiceId][] = $vote;
        }

        return $grouped;
    }
}
