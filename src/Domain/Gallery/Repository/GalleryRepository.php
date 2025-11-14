<?php

namespace App\Domain\Gallery\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Gallery\Entity\Gallery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Gallery>
 *
 * @method null|Gallery find($id, $lockMode = null, $lockVersion = null)
 * @method null|Gallery findOneBy(array $criteria, array $orderBy = null)
 * @method Gallery[]    findAll()
 * @method Gallery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GalleryRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gallery::class);
    }

    /**
     * @return Gallery[]
     */
    public function findByLarp(Larp $larp): array
    {
        return $this->createQueryBuilder('g')
            ->addSelect('photographer', 'user')
            ->join('g.photographer', 'photographer')
            ->join('photographer.user', 'user')
            ->where('g.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Gallery[]
     */
    public function findByPhotographer(LarpParticipant $photographer): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.photographer = :photographer')
            ->setParameter('photographer', $photographer)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
