<?php

namespace App\Repository;

use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Entity\User;
use App\Enum\LarpStageStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Larp>
 */
class LarpRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Larp::class);
    }

    //    /**
    //     * @return Larp[] Returns an array of Larp objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Larp
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return Larp[]
     */
    public function findAllUpcomingPublished(?User $currentUser = null): array
    {
        $now = new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.startDate', 'ASC');

        $upcoming = $qb->expr()->andX(
            $qb->expr()->eq('l.status', ':published'),
            $qb->expr()->gte('l.startDate', ':now')
        );

        if ($currentUser) {
            $subQb = $this->getEntityManager()->createQueryBuilder();
            $subQb->select('1')
                ->from(LarpParticipant::class, 'lp')
                ->where('lp.larp = l')
                ->andWhere('lp.user = :currentUser');

            $qb->where(
                $qb->expr()->orX(
                    $upcoming,
                    $qb->expr()->exists($subQb->getDQL())
                )
            );
            $qb->setParameter('currentUser', $currentUser);
        } else {
            // If no user is logged in, only published upcoming larps are shown.
            $qb->where($upcoming);
        }

        $qb->setParameter('published', LarpStageStatus::PUBLISHED->value)
            ->setParameter('now', $now);

        return $qb->getQuery()->getResult();
    }

    public function findAllWhereParticipating(User $user): array
    {
        $qb = $this->createQueryBuilder('l');

        $subQb = $this->getEntityManager()->createQueryBuilder();
        $subQb->select('1')
            ->from(LarpParticipant::class, 'lp')
            ->where('lp.larp = l')
            ->andWhere('lp.user = :currentUser');

        $qb->where(
            $qb->expr()->exists($subQb->getDQL())
        );
        $qb->setParameter('currentUser', $user);

        return $qb->getQuery()->getResult();
    }
}
