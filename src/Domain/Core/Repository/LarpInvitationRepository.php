<?php

namespace App\Domain\Core\Repository;

use App\Domain\Core\Entity\LarpInvitation;
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
}
