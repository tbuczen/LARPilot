<?php

namespace App\Repository;

use App\Entity\Enum\LarpStageStatus;
use App\Entity\Larp;
use App\Entity\LarpParticipant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Larp>
 */
class LarpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Larp::class);
    }

    public function modifyListQueryBuilderForUser(QueryBuilder $qb, ?UserInterface $user): QueryBuilder
    {
        $now = new \DateTimeImmutable();
        $visibleStatuses = [
            LarpStageStatus::PUBLISHED->value,
            LarpStageStatus::INQUIRIES->value,
            LarpStageStatus::CONFIRMED->value,
            LarpStageStatus::COMPLETED->value,
            LarpStageStatus::CANCELLED->value,
        ];

        $upcoming = $qb->expr()->andX(
            $qb->expr()->in('c.status', ':visibleStatuses'),
            $qb->expr()->gte('c.startDate', ':now')
        );

        if ($user) {
            $subQb = $this->getEntityManager()->createQueryBuilder();
            $subQb->select('1')
                ->from(LarpParticipant::class, 'lp')
                ->where('lp.larp = c')
                ->andWhere('lp.user = :currentUser');

            $qb->where(
                $qb->expr()->orX(
                    $upcoming,
                    $qb->expr()->exists($subQb->getDQL())
                )
            );
            $qb->setParameter('currentUser', $user);
        }

        else {
            // If no user is logged in, only visible upcoming larps are shown.
            $qb->where($upcoming);
        }

        $qb->setParameter('visibleStatuses', $visibleStatuses)
            ->setParameter('now', $now);


        return $qb;
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['status'])) {
            $qb->andWhere('l.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['setting'])) {
            $qb->andWhere('l.setting = :setting')
               ->setParameter('setting', $filters['setting']);
        }

        if (!empty($filters['type'])) {
            $qb->andWhere('l.type = :type')
               ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['characterSystem'])) {
            $qb->andWhere('l.characterSystem = :characterSystem')
               ->setParameter('characterSystem', $filters['characterSystem']);
        }

        if (!empty($filters['location'])) {
            $qb->andWhere('loc.city LIKE :location OR loc.country LIKE :location OR loc.title LIKE :location OR loc.address LIKE :location')
               ->setParameter('location', '%' . $filters['location'] . '%');
        }

        if (!empty($filters['dateFrom'])) {
            $qb->andWhere('l.startDate >= :dateFrom')
               ->setParameter('dateFrom', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $qb->andWhere('l.endDate <= :dateTo')
               ->setParameter('dateTo', $filters['dateTo']);
        }

        if (!empty($filters['minDuration'])) {
            $qb->andWhere('DATEDIFF(l.endDate, l.startDate) + 1 >= :minDuration')
               ->setParameter('minDuration', $filters['minDuration']);
        }

        if (!empty($filters['maxDuration'])) {
            $qb->andWhere('DATEDIFF(l.endDate, l.startDate) + 1 <= :maxDuration')
               ->setParameter('maxDuration', $filters['maxDuration']);
        }
    }

}