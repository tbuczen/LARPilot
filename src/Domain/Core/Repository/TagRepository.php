<?php

namespace App\Domain\Core\Repository;

use App\Domain\Core\Entity\Tag;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Tag>
 *
 * @method null|Tag find($id, $lockMode = null, $lockVersion = null)
 * @method null|Tag findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * Find a tag by its title (case-insensitive) for a specific LARP.
     */
    public function findByTitleForLarp(string $title, string $larpId): ?Tag
    {
        return $this->createQueryBuilder('t')
            ->where('LOWER(t.title) = LOWER(:title)')
            ->andWhere('t.larp = :larpId')
            ->setParameter('title', $title)
            ->setParameter('larpId', $larpId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
