<?php

declare(strict_types=1);

namespace App\Domain\Survey\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Survey\Entity\SurveyQuestion;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<SurveyQuestion>
 *
 * @method null|SurveyQuestion find($id, $lockMode = null, $lockVersion = null)
 * @method null|SurveyQuestion findOneBy(array $criteria, array $orderBy = null)
 * @method SurveyQuestion[]    findAll()
 * @method SurveyQuestion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyQuestionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyQuestion::class);
    }
}
