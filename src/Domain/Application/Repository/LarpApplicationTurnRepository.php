<?php

declare(strict_types=1);

namespace App\Domain\Application\Repository;

use App\Domain\Application\Entity\LarpApplicationTurn;
use App\Domain\Core\Entity\Larp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LarpApplicationTurn>
 */
class LarpApplicationTurnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpApplicationTurn::class);
    }

    /** @return LarpApplicationTurn[] */
    public function findByLarpOrdered(Larp $larp): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('t.roundNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextRoundNumber(Larp $larp): int
    {
        $max = $this->createQueryBuilder('t')
            ->select('MAX(t.roundNumber)')
            ->where('t.larp = :larp')
            ->setParameter('larp', $larp)
            ->getQuery()
            ->getSingleScalarResult();

        return ($max ?? 0) + 1;
    }
}
