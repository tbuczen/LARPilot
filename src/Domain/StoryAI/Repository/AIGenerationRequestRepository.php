<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Infrastructure\Repository\BaseRepository;
use App\Domain\StoryAI\Entity\AIGenerationRequest;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<AIGenerationRequest>
 */
class AIGenerationRequestRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AIGenerationRequest::class);
    }

    /**
     * @return AIGenerationRequest[]
     */
    public function findByLarp(Larp $larp, int $limit = 20): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @TODO: Implement usage statistics methods (Phase 5)
     * - getTotalRequestsByLarp()
     * - getTotalTokensUsedByLarp()
     * - getAverageResponseTime()
     */
}
