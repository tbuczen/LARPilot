<?php

namespace App\Service\Larp;

use App\Entity\Larp;
use App\Entity\LarpCharacterSubmission;
use App\Repository\LarpCharacterSubmissionRepository;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;

readonly class SubmissionStatsService
{
    public function __construct(
        private LarpCharacterSubmissionRepository $repository,
        private EntityPreloader $preloader,
    ) {
    }

    public function getStatsForLarp(Larp $larp): array
    {
        $submissions = $this->repository->findBy(['larp' => $larp]);
        $this->preloader->preload($submissions, 'choices');
        $this->preloader->preload($submissions, 'choices.character');
        $this->preloader->preload($larp->getFactions(), 'members');

        $charactersWithSubmission = [];
        foreach ($submissions as $submission) {
            foreach ($submission->getChoices() as $choice) {
                $charactersWithSubmission[$choice->getCharacter()->getId()->toRfc4122()] = true;
            }
        }

        $missing = 0;
        foreach ($larp->getCharacters() as $character) {
            if (!isset($charactersWithSubmission[$character->getId()->toRfc4122()])) {
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
                if (isset($charactersWithSubmission[$member->getId()->toRfc4122()])) {
                    $with++;
                }
            }
            $factionStats[] = [
                'faction' => $faction,
                'percentage' => round($with / $total * 100, 2),
            ];
        }

        return [
            'submissions' => $submissions,
            'missing' => $missing,
            'factionStats' => $factionStats,
        ];
    }
}
