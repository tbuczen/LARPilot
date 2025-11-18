<?php

declare(strict_types=1);

namespace App\Domain\Survey\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Survey\Entity\Survey;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Survey>
 *
 * @method null|Survey find($id, $lockMode = null, $lockVersion = null)
 * @method null|Survey findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Survey::class);
    }
}
