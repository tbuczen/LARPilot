<?php

namespace App\Domain\Core\Repository;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<LarpParticipant>
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

    /**
     * Count the number of organizers for a given LARP
     */
    public function countMainOrganizersForLarp(Larp $larp): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id)')
            ->where('p.larp = :larp')
            ->setParameter('larp', $larp);

        $qb->andWhere("JSONB_EXISTS(p.roles, :role) = true");
        $qb->setParameter("role", ParticipantRole::ORGANIZER->value);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
