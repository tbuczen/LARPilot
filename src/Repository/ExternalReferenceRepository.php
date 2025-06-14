<?php

namespace App\Repository;

use App\Entity\ExternalReference;
use App\Entity\TargetableInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExternalReference>
 *
 * @method null|ExternalReference find($id, $lockMode = null, $lockVersion = null)
 * @method null|ExternalReference findOneBy(array $criteria, array $orderBy = null)
 * @method ExternalReference[]    findAll()
 * @method ExternalReference[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExternalReferenceRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalReference::class);
    }

    public function findByTarget(TargetableInterface $target): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.targetType = :type')
            ->andWhere('r.targetId = :id')
            ->setParameters(new ArrayCollection([
                new Parameter('type', $target->getTargetType()->value),
                new Parameter('id', $target->getId())
            ]))
            ->getQuery()
            ->getResult();
    }
}
