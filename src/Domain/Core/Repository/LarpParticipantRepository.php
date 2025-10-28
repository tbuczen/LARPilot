<?php

namespace App\Domain\Core\Repository;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\LarpParticipant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<\App\Domain\Core\Controller\Backoffice\\App\Domain\Core\Entity\LarpParticipant>
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
        parent::__construct($registry, LarpParticipant::class);
    }

    /**
     * @return LarpParticipant[]
     */
    public function findForUserWithCharacters(User $user): array
    {
        return $this->createQueryBuilder('participant')
            ->addSelect('larp', 'characters')
            ->join('participant.larp', 'larp')
            ->leftJoin('participant.larpCharacters', 'characters')
            ->where('participant.user = :user')
            ->setParameter('user', $user)
            ->orderBy('larp.startDate', 'DESC')
            ->addOrderBy('larp.title', 'ASC')
            ->addOrderBy('characters.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
