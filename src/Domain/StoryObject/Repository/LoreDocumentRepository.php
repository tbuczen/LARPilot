<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\StoryObject\Entity\Enum\LoreDocumentCategory;
use App\Domain\StoryObject\Entity\LoreDocument;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<LoreDocument>
 *
 * @method null|LoreDocument find($id, $lockMode = null, $lockVersion = null)
 * @method null|LoreDocument findOneBy(array $criteria, array $orderBy = null)
 * @method LoreDocument[]    findAll()
 * @method LoreDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoreDocumentRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoreDocument::class);
    }

    /**
     * Find all active lore documents for a LARP, ordered by priority.
     *
     * @return LoreDocument[]
     */
    public function findActiveByLarp(Larp $larp): array
    {
        return $this->createQueryBuilder('ld')
            ->where('ld.larp = :larp')
            ->andWhere('ld.active = true')
            ->setParameter('larp', $larp)
            ->orderBy('ld.priority', 'DESC')
            ->addOrderBy('ld.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find lore documents that should always be included in AI context.
     *
     * @return LoreDocument[]
     */
    public function findAlwaysInclude(Larp $larp): array
    {
        return $this->createQueryBuilder('ld')
            ->where('ld.larp = :larp')
            ->andWhere('ld.active = true')
            ->andWhere('ld.alwaysIncludeInContext = true')
            ->setParameter('larp', $larp)
            ->orderBy('ld.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find lore documents by category.
     *
     * @return LoreDocument[]
     */
    public function findByCategory(Larp $larp, LoreDocumentCategory $category): array
    {
        return $this->createQueryBuilder('ld')
            ->where('ld.larp = :larp')
            ->andWhere('ld.active = true')
            ->andWhere('ld.category = :category')
            ->setParameter('larp', $larp)
            ->setParameter('category', $category)
            ->orderBy('ld.priority', 'DESC')
            ->addOrderBy('ld.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count active lore documents for a LARP.
     */
    public function countActiveByLarp(Larp $larp): int
    {
        return (int) $this->createQueryBuilder('ld')
            ->select('COUNT(ld.id)')
            ->where('ld.larp = :larp')
            ->andWhere('ld.active = true')
            ->setParameter('larp', $larp)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Create a query builder for filtering lore documents.
     */
    public function createFilteredQueryBuilder(Larp $larp): QueryBuilder
    {
        return $this->createQueryBuilder('ld')
            ->where('ld.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('ld.priority', 'DESC')
            ->addOrderBy('ld.title', 'ASC');
    }
}
