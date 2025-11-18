<?php

declare(strict_types=1);

namespace App\Domain\Survey\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Survey\Entity\SurveyResponse;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<SurveyResponse>
 *
 * @method null|SurveyResponse find($id, $lockMode = null, $lockVersion = null)
 * @method null|SurveyResponse findOneBy(array $criteria, array $orderBy = null)
 * @method SurveyResponse[]    findAll()
 * @method SurveyResponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyResponseRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyResponse::class);
    }

    /**
     * @return SurveyResponse[]
     */
    public function findByLarpWithRelations(Larp $larp): array
    {
        return $this->createQueryBuilder('sr')
            ->addSelect('s', 'u', 'a', 'q', 'c')
            ->leftJoin('sr.survey', 's')
            ->leftJoin('sr.user', 'u')
            ->leftJoin('sr.answers', 'a')
            ->leftJoin('a.question', 'q')
            ->leftJoin('sr.assignedCharacter', 'c')
            ->where('sr.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('sr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
