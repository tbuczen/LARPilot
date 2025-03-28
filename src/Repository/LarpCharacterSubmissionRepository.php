<?php

namespace App\Repository;

use App\Entity\LarpCharacterSubmission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LarpCharacterSubmission>
 *
 * @method null|LarpCharacterSubmission find($id, $lockMode = null, $lockVersion = null)
 * @method null|LarpCharacterSubmission findOneBy(array $criteria, array $orderBy = null)
 * @method LarpCharacterSubmission[]    findAll()
 * @method LarpCharacterSubmission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LarpCharacterSubmissionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpCharacterSubmission::class);
    }

}
