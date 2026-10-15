<?php

namespace App\Domain\CallTranscript\Repository;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<CallRecording>
 */
class CallRecordingRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CallRecording::class);
    }

    /**
     * @return list<CallRecording>
     */
    public function findForLarp(Larp $larp): array
    {
        return $this->createQueryBuilder('recording')
            ->andWhere('recording.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('recording.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
