<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryAI\Entity\StoryObjectEmbedding;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoryObjectEmbedding>
 *
 * @method StoryObjectEmbedding|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoryObjectEmbedding|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoryObjectEmbedding[]    findAll()
 * @method StoryObjectEmbedding[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoryObjectEmbeddingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoryObjectEmbedding::class);
    }

    public function findByStoryObject(StoryObject $storyObject): ?StoryObjectEmbedding
    {
        return $this->findOneBy(['storyObject' => $storyObject]);
    }

    /**
     * @return StoryObjectEmbedding[]
     */
    public function findByLarp(Larp $larp): array
    {
        return $this->findBy(['larp' => $larp]);
    }

    /**
     * Perform vector similarity search using pgvector.
     *
     * @param array<int, float> $queryEmbedding The query vector
     * @param Larp $larp The LARP to search within
     * @param int $limit Maximum number of results
     * @param float $minSimilarity Minimum cosine similarity threshold (0-1)
     * @return array<array{embedding: StoryObjectEmbedding, similarity: float}>
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
        // pgvector uses <=> for cosine distance, so similarity = 1 - distance
        $sql = <<<SQL
            SELECT
                soe.id,
                1 - (soe.embedding::vector <=> :query_vector::vector) as similarity
            FROM story_object_embedding soe
            WHERE soe.larp_id = :larp_id
              AND 1 - (soe.embedding::vector <=> :query_vector::vector) >= :min_similarity
            ORDER BY soe.embedding::vector <=> :query_vector::vector
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
            $embedding = $this->find($row['id']);
            if ($embedding) {
                $results[] = [
                    'embedding' => $embedding,
                    'similarity' => (float) $row['similarity'],
                ];
            }
        }

        return $results;
    }

    /**
     * Find embeddings that need updating (content hash doesn't match).
     *
     * @return StoryObjectEmbedding[]
     */
    public function findOutdated(Larp $larp): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.larp = :larp')
            ->setParameter('larp', $larp)
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete all embeddings for a LARP.
     */
    public function deleteByLarp(Larp $larp): int
    {
        return $this->createQueryBuilder('e')
            ->delete()
            ->where('e.larp = :larp')
            ->setParameter('larp', $larp)
            ->getQuery()
            ->execute();
    }

    /**
     * Check if an embedding exists for a story object.
     */
    public function existsForStoryObject(StoryObject $storyObject): bool
    {
        return $this->count(['storyObject' => $storyObject]) > 0;
    }

    /**
     * Get count of embeddings for a LARP.
     */
    public function countByLarp(Larp $larp): int
    {
        return $this->count(['larp' => $larp]);
    }
}
