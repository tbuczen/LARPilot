<?php

namespace App\Domain\Application\Repository;

use App\Domain\Application\Entity\LarpApplicationVote;
use App\Domain\Core\Repository\BaseRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<\App\Domain\Application\Entity\LarpApplicationVote>
 *
 * @method null|LarpApplicationVote find($id, $lockMode = null, $lockVersion = null)
 * @method null|LarpApplicationVote findOneBy(array $criteria, array $orderBy = null)
 * @method LarpApplicationVote[]    findAll()
 * @method LarpApplicationVote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LarpApplicationVoteRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpApplicationVote::class);
    }
}
