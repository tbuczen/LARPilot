<?php

namespace App\Repository;

use App\Entity\LarpInvitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LarpInvitation>
 *
 * @method null|LarpInvitation find($id, $lockMode = null, $lockVersion = null)
 * @method null|LarpInvitation findOneBy(array $criteria, array $orderBy = null)
 * @method LarpInvitation[]    findAll()
 * @method LarpInvitation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LarpInvitationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpInvitation::class);
    }

    //    /**
    //     * @return LarpInvitation[] Returns an array of LarpInvitation objects
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

    //    public function findOneBySomeField($value): ?LarpInvitation
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
