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

    /**
     * Find a participant by user's full name (firstName + lastName) for a specific LARP.
     * The name can be provided as "FirstName LastName" or "LastName FirstName".
     */
    public function findByUserFullName(string $fullName, string $larpId): ?LarpParticipant
    {
        $fullName = trim($fullName);
        if (empty($fullName)) {
            return null;
        }

        // Try exact match first: "firstName lastName"
        $result = $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->where('p.larp = :larpId')
            ->andWhere("LOWER(CONCAT(u.firstName, ' ', u.lastName)) = LOWER(:fullName)")
            ->setParameter('larpId', $larpId)
            ->setParameter('fullName', $fullName)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($result !== null) {
            return $result;
        }

        // Try reverse order: "lastName firstName"
        return $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->where('p.larp = :larpId')
            ->andWhere("LOWER(CONCAT(u.lastName, ' ', u.firstName)) = LOWER(:fullName)")
            ->setParameter('larpId', $larpId)
            ->setParameter('fullName', $fullName)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
