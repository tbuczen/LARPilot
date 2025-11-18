<?php

declare(strict_types=1);

namespace App\Domain\Survey\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Survey\Entity\SurveyAnswer;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<SurveyAnswer>
 *
 * @method null|SurveyAnswer find($id, $lockMode = null, $lockVersion = null)
 * @method null|SurveyAnswer findOneBy(array $criteria, array $orderBy = null)
 * @method SurveyAnswer[]    findAll()
 * @method SurveyAnswer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyAnswerRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyAnswer::class);
    }
}
