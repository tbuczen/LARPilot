<?php

namespace App\Service\Larp;

use App\Entity\Larp;
use App\Entity\LarpApplication;
use App\Repository\LarpApplicationRepository;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;

readonly class SubmissionStatsService
{
    public function __construct(
        private LarpApplicationRepository $repository,
        private EntityPreloader $preloader,
    ) {
    }

    public function getStatsForLarp(Larp $larp): array
    {
        $applications = $this->repository->findBy(['larp' => $larp]);
        $this->preloader->preload($applications, 'choices');
        $this->preloader->preload($applications, 'choices.character');
        $this->preloader->preload($larp->getFactions()->toArray(), 'members');

        $charactersWithApplication = [];
        foreach ($applications as $application) {
            foreach ($application->getChoices() as $choice) {
                $charactersWithApplication[$choice->getCharacter()->getId()->toRfc4122()] = true;
            }
        }

        $missing = 0;
        foreach ($larp->getCharacters() as $character) {
            if (!isset($charactersWithApplication[$character->getId()->toRfc4122()])) {
                $missing++;
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
            'missing' => $missing,
            'factionStats' => $factionStats,
        ];
    }
}
