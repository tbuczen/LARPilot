<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\Entity\LarpLoreDocument;
use App\Domain\StoryAI\Entity\LoreDocumentChunk;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoreDocumentChunk>
 *
 * @method LoreDocumentChunk|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoreDocumentChunk|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoreDocumentChunk[]    findAll()
 * @method LoreDocumentChunk[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoreDocumentChunkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoreDocumentChunk::class);
    }

    /**
     * Get all chunks for a document ordered by index.
     *
     * @return LoreDocumentChunk[]
     */
    public function findByDocument(LarpLoreDocument $document): array
    {
        return $this->findBy(
            ['document' => $document],
            ['chunkIndex' => 'ASC']
        );
    }

    /**
     * Perform a vector similarity search on lore chunks.
     *
     * @param array<int, float> $queryEmbedding The query vector
     * @param Larp $larp The LARP to search within
     * @param int $limit Maximum number of results
     * @param float $minSimilarity Minimum cosine similarity threshold (0-1)
     * @return array<array{chunk: LoreDocumentChunk, similarity: float}>
     * @throws Exception
     */
    public function findSimilar(
        array $queryEmbedding,
        Larp $larp,
        int $limit = 10,
        float $minSimilarity = 0.5
    ): array {
        $conn = $this->getEntityManager()->getConnection();

        // Convert embedding to pgvector format
        $vectorStr = '[' . implode(',', $queryEmbedding) . ']';

        // Use cosine similarity (1 - cosine distance)
        $sql = <<<SQL
            SELECT
                ldc.id,
                1 - (ldc.embedding::vector <=> :query_vector::vector) as similarity
            FROM lore_document_chunk ldc
            INNER JOIN larp_lore_document lld ON ldc.document_id = lld.id
            WHERE ldc.larp_id = :larp_id
              AND lld.active = true
              AND 1 - (ldc.embedding::vector <=> :query_vector::vector) >= :min_similarity
            ORDER BY ldc.embedding::vector <=> :query_vector::vector
            LIMIT :limit
        SQL;

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'query_vector' => $vectorStr,
            'larp_id' => $larp->getId()->toRfc4122(),
            'min_similarity' => $minSimilarity,
            'limit' => $limit,
        ]);

        $rows = $result->fetchAllAssociative();
        $results = [];

        foreach ($rows as $row) {
            $chunk = $this->find($row['id']);
            if ($chunk) {
                $results[] = [
                    'chunk' => $chunk,
                    'similarity' => (float) $row['similarity'],
                ];
            }
        }

        return $results;
    }

    /**
     * Delete all chunks for a document.
     */
    public function deleteByDocument(LarpLoreDocument $document): int
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->where('c.document = :document')
            ->setParameter('document', $document)
            ->getQuery()
            ->execute();
    }

    /**
     * Get count of chunks for a LARP.
     */
    public function countByLarp(Larp $larp): int
    {
        return $this->count(['larp' => $larp]);
    }
}
