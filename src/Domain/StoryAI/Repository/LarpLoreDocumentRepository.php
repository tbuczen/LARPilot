<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\Entity\Enum\LoreDocumentType;
use App\Domain\StoryAI\Entity\LarpLoreDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LarpLoreDocument>
 *
 * @method LarpLoreDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method LarpLoreDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method LarpLoreDocument[]    findAll()
 * @method LarpLoreDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LarpLoreDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpLoreDocument::class);
    }

    /**
     * Get all active documents for a LARP ordered by priority.
     *
     * @return LarpLoreDocument[]
     */
    public function findActiveByLarp(Larp $larp): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.larp = :larp')
            ->andWhere('d.active = true')
            ->setParameter('larp', $larp)
            ->orderBy('d.priority', 'DESC')
            ->addOrderBy('d.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get documents that should always be included in AI context.
     *
     * @return LarpLoreDocument[]
     */
    public function findAlwaysInclude(Larp $larp): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.larp = :larp')
            ->andWhere('d.active = true')
            ->andWhere('d.alwaysInclude = true')
            ->setParameter('larp', $larp)
            ->orderBy('d.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get documents by type.
     *
     * @return LarpLoreDocument[]
     */
    public function findByType(Larp $larp, LoreDocumentType $type): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.larp = :larp')
            ->andWhere('d.type = :type')
            ->andWhere('d.active = true')
            ->setParameter('larp', $larp)
            ->setParameter('type', $type)
            ->orderBy('d.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total content length for all active documents in a LARP.
     */
    public function getTotalContentLength(Larp $larp): int
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(LENGTH(d.content)) as total')
            ->where('d.larp = :larp')
            ->andWhere('d.active = true')
            ->setParameter('larp', $larp)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Get count of documents by LARP.
     */
    public function countByLarp(Larp $larp): int
    {
        return $this->count(['larp' => $larp]);
    }

    /**
     * Get count of active documents by LARP.
     */
    public function countActiveByLarp(Larp $larp): int
    {
        return $this->count(['larp' => $larp, 'active' => true]);
    }
}
