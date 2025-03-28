<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserSocialAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSocialAccount>
 *
 * @method null|UserSocialAccount find($id, $lockMode = null, $lockVersion = null)
 * @method null|UserSocialAccount findOneBy(array $criteria, array $orderBy = null)
 * @method UserSocialAccount[]    findAll()
 * @method UserSocialAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserSocialAccountRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSocialAccount::class);
    }

    //    /**
    //     * @return SocialAccount[] Returns an array of SocialAccount objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?SocialAccount
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @param User $user
     * @return UserSocialAccount[]
     */
    public function getAllBelongingToUser(User $user): array
    {
        return $this->createQueryBuilder('sa')
            ->andWhere('sa.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
