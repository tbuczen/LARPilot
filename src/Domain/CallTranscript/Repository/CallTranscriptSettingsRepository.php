<?php

namespace App\Domain\CallTranscript\Repository;

use App\Domain\CallTranscript\Entity\CallTranscriptSettings;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<CallTranscriptSettings>
 */
class CallTranscriptSettingsRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CallTranscriptSettings::class);
    }

    public function forLarp(Larp $larp): CallTranscriptSettings
    {
        return $this->findOneBy(['larp' => $larp]) ?? new CallTranscriptSettings($larp);
    }
}
