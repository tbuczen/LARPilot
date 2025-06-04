<?php

namespace App\Repository;

use App\Entity\SavedFormFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<SavedFormFilter>
 *
 * @method null|SavedFormFilter find($id, $lockMode = null, $lockVersion = null)
 * @method null|SavedFormFilter findOneBy(array $criteria, array $orderBy = null)
 * @method SavedFormFilter[]    findAll()
 * @method SavedFormFilter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SavedFormFilterRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SavedFormFilter::class);
    }

    /**
     * @return SavedFormFilter[]
     */
    public function findByFormNameAndUser(string $formName, UserInterface $user, null|string|Uuid $larpId = null): array
    {
        if (is_string($larpId) && !empty($larpId)) {
            $larpId = Uuid::fromString($larpId);
        }

        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.formName = :formName')
            ->andWhere('f.createdBy = :user')
            ->setParameter('formName', $formName)
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC');

        if($larpId) {
            $qb->andWhere('f.larp = :larp')
                ->setParameter('larp', $larpId);
        }

        return $qb->getQuery()
        ->getResult();
    }

}
