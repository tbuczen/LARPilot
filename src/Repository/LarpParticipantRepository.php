<?php

namespace App\Repository;

use App\Entity\LarpParticipant;
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
        parent::__construct($registry, LarpParticipant::class);
    }
}
