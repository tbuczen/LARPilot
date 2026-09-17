<?php

namespace App\Domain\StoryObject\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Core\Repository\ListableRepositoryInterface;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Entity\KnowledgeDocument;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<KnowledgeDocument>
 *
 * @method null|KnowledgeDocument find($id, $lockMode = null, $lockVersion = null)
 * @method null|KnowledgeDocument findOneBy(array $criteria, array $orderBy = null)
 * @method KnowledgeDocument[]    findAll()
 * @method KnowledgeDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KnowledgeDocumentRepository extends BaseRepository implements ListableRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KnowledgeDocument::class);
    }

    public function decorateLarpListQueryBuilder(QueryBuilder $qb, Larp $larp): QueryBuilder
    {
        return $qb
            ->andWhere('kd.larp = :larp')
            ->setParameter('larp', $larp);
    }

    // ========================================================================
    // Owner-based Queries
    // ========================================================================

    /**
     * Find all documents owned by a specific StoryObject
     *
     * @return KnowledgeDocument[]
     */
    public function findByOwner(StoryObject $owner): array
    {
        return $this->createQueryBuilder('kd')
            ->join('kd.owners', 'o')
            ->where('o = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('kd.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all LARP-wide documents (no specific owner)
     *
     * @return KnowledgeDocument[]
     */
    public function findLarpDocuments(Larp $larp): array
    {
        return $this->createQueryBuilder('kd')
            ->leftJoin('kd.owners', 'o')
            ->where('kd.larp = :larp')
            ->andWhere('o.id IS NULL')
            ->setParameter('larp', $larp)
            ->orderBy('kd.category', 'ASC')
            ->addOrderBy('kd.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ========================================================================
    // Visibility Queries
    // ========================================================================

    /**
     * Create a query builder for documents visible to a specific character
     * Includes:
     * - Public documents
     * - Documents owned by this character
     * - Documents owned by character's factions
     * - Documents with explicit visibility to character
     * - Documents with explicit visibility to character's factions
     */
    public function createVisibleToCharacterQueryBuilder(Character $character): QueryBuilder
    {
        $qb = $this->createQueryBuilder('kd');

        // Join owners and visibility relationships
        $qb->leftJoin('kd.owners', 'owners')
            ->leftJoin('kd.visibleTo', 'visibleTo')
            ->where('kd.larp = :larp')
            ->andWhere(
                $qb->expr()->orX(
                    // Public documents
                    'kd.isPublic = true',
                    // Owned by this character
                    'owners.id = :characterId',
                    // Owned by character's faction(s)
                    'owners IN (SELECT IDENTITY(fm.faction) FROM ' . Character::class . ' c JOIN c.factions fm WHERE c.id = :characterId)',
                    // Explicit visibility to this character
                    'visibleTo.id = :characterId',
                    // Explicit visibility to character's faction(s)
                    'visibleTo IN (SELECT IDENTITY(fm2.faction) FROM ' . Character::class . ' c2 JOIN c2.factions fm2 WHERE c2.id = :characterId)'
                )
            )
            ->setParameter('larp', $character->getLarp())
            ->setParameter('characterId', $character->getId())
            ->distinct();

        return $qb;
    }

    /**
     * Find all documents visible to a specific character
     *
     * @return KnowledgeDocument[]
     */
    public function findVisibleToCharacter(Character $character): array
    {
        return $this->createVisibleToCharacterQueryBuilder($character)
            ->orderBy('kd.category', 'ASC')
            ->addOrderBy('kd.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
