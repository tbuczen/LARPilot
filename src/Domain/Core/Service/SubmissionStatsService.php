<?php

namespace App\Domain\Core\Service;

use App\Domain\Application\Repository\LarpApplicationRepository;
use App\Domain\Core\Entity\Larp;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;

readonly class SubmissionStatsService
{
    public function __construct(
        private LarpApplicationRepository $applicationRepository,
        private EntityPreloader           $preloader,
    ) {
    }

    public function getStatsForLarp(Larp $larp): array
    {
        $applications = $this->applicationRepository->findBy(['larp' => $larp]);
        $this->preloader->preload($applications, 'choices');
        //        $this->preloader->preload($applications, 'choices.character');
        $this->preloader->preload($larp->getFactions()->toArray(), 'members');

        $charactersWithApplication = [];
        foreach ($applications as $application) {
            foreach ($application->getChoices() as $choice) {
                $charactersWithApplication[$choice->getCharacter()->getId()->toRfc4122()] = true;
            }
        }

        $factionStats = [];
        foreach ($larp->getFactions() as $faction) {
            $total = count($faction->getMembers());
            if ($total === 0) {
                continue;
            }
            $with = 0;
            foreach ($faction->getMembers() as $member) {
                if (isset($charactersWithApplication[$member->getId()->toRfc4122()])) {
                    $with++;
                }
            }
            $factionStats[] = [
                'faction' => $faction,
                'percentage' => round($with / $total * 100, 2),
            ];
        }

        return [
            'applications' => $applications,
            'factionStats' => $factionStats,
        ];
    }
}
