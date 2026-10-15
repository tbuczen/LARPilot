<?php

namespace App\Domain\CallTranscript\Repository;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\CallRecordingTrack;
use App\Domain\CallTranscript\Entity\Enum\CallTrackStatus;
use App\Domain\Core\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<CallRecordingTrack>
 */
class CallRecordingTrackRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CallRecordingTrack::class);
    }

    public function countPending(CallRecording $recording): int
    {
        return (int) $this->createQueryBuilder('track')
            ->select('COUNT(track.id)')
            ->andWhere('track.recording = :recording')
            ->andWhere('track.status = :status')
            ->setParameter('recording', $recording)
            ->setParameter('status', CallTrackStatus::PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
