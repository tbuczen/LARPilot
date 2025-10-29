<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Repository;

use App\Domain\Infrastructure\Repository\BaseRepository;
use App\Domain\StoryAI\Entity\AIGenerationRequest;
use App\Domain\StoryAI\Entity\AIGenerationResult;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<AIGenerationResult>
 */
class AIGenerationResultRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AIGenerationResult::class);
    }

    /**
     * @return AIGenerationResult[]
     */
    public function findByRequest(AIGenerationRequest $request): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.request = :request')
            ->setParameter('request', $request)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getAcceptanceRate(AIGenerationRequest $request): float
    {
        $total = $this->count(['request' => $request]);
        if ($total === 0) {
            return 0.0;
        }

        $accepted = $this->count(['request' => $request, 'accepted' => true]);
        return ($accepted / $total) * 100;
    }
}
