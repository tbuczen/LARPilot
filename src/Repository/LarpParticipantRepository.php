<?php

namespace App\Repository;

use App\Entity\LarpParticipant;
use App\Entity\StoryObject\LarpFaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LarpParticipant>
 *
 * @method null|LarpParticipant find($id, $lockMode = null, $lockVersion = null)
 * @method null|LarpParticipant findOneBy(array $criteria, array $orderBy = null)
 * @method LarpParticipant[]    findAll()
 * @method LarpParticipant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LarpParticipantRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpFaction::class);
    }

    //    /**
    //     * @return LarpParticipant[] Returns an array of LarpParticipant objects
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

    //    public function findOneBySomeField($value): ?LarpParticipant
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
