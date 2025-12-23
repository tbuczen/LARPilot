<?php

namespace App\Domain\Core\Repository;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends BaseRepository<Larp>
 */
class LarpRepository extends BaseRepository
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
        ];

        $upcoming = $qb->expr()->andX(
            $qb->expr()->in('c.status', ':visibleStatuses'),
            $qb->expr()->gte('c.startDate', ':now')
        );

        if ($user instanceof UserInterface) {
            $subQb = $this->getEntityManager()->createQueryBuilder();
            $subQb->select('1')
                ->from(LarpParticipant::class, 'lp')
                ->where('lp.larp = c')
                ->andWhere('lp.user = :currentUser');

            $qb->andWhere(
                $qb->expr()->orX(
                    $upcoming,
                    $qb->expr()->exists($subQb->getDQL())
                )
            );
            $qb->setParameter('currentUser', $user);
        } else {
            $qb->andWhere($upcoming);
        }

        $qb->setParameter('visibleStatuses', $visibleStatuses)
            ->setParameter('now', $now);


        return $qb;
    }

    public function findAllWhereParticipating(User $user): array
    {
        $qb = $this->createQueryBuilder('l');

        $subQb = $this->getEntityManager()->createQueryBuilder();
        $subQb->select('1')
            ->from(LarpParticipant::class, 'lp')
            ->where('lp.larp = l')
            ->andWhere('lp.user = :currentUser');


        $qb->where(
            $qb->expr()->exists($subQb->getDQL())
        );
        $qb->setParameter('currentUser', $user);

        return $qb->getQuery()->getResult();
    }
}
