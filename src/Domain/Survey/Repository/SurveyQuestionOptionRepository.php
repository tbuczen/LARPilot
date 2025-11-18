<?php

declare(strict_types=1);

namespace App\Domain\Survey\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Survey\Entity\SurveyQuestionOption;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<SurveyQuestionOption>
 *
 * @method null|SurveyQuestionOption find($id, $lockMode = null, $lockVersion = null)
 * @method null|SurveyQuestionOption findOneBy(array $criteria, array $orderBy = null)
 * @method SurveyQuestionOption[]    findAll()
 * @method SurveyQuestionOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyQuestionOptionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyQuestionOption::class);
    }
}
