<?php

namespace App\Domain\Core\Service;

use App\Domain\Application\Entity\LarpApplication;
use App\Domain\Application\Repository\LarpApplicationRepository;
use App\Domain\Core\Entity\Larp;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;

readonly class LarpApplicationDashboardService
{
    public function __construct(
        private EntityPreloader           $entityPreloader,
        private LarpApplicationRepository $applicationRepository
    ) {
    }

    public function getApplicationsWithPreloading(Larp $larp, $queryBuilder = null): array
    {
        // Get the applications with all necessary joins
        $qb = $queryBuilder ?: $this->applicationRepository->createQueryBuilder('a');
        
        if (!$queryBuilder) {
            $qb->leftJoin('a.choices', 'choice')
                ->leftJoin('choice.character', 'character')
                ->leftJoin('character.factions', 'faction')
                ->addSelect('choice', 'character', 'faction')
                ->andWhere('a.larp = :larp')
                ->setParameter('larp', $larp)
                ->orderBy('a.createdAt', 'DESC');
        }

        $applications = $qb->getQuery()->getResult();

        // Preload additional relationships that might be needed
        if (!empty($applications)) {
            $this->entityPreloader->preload($applications, 'user');
            $this->entityPreloader->preload($applications, 'choices');
            
            // Get all choices for further preloading
            $allChoices = [];
            foreach ($applications as $application) {
                foreach ($application->getChoices() as $choice) {
                    $allChoices[] = $choice;
                }
            }
            
            if ($allChoices !== []) {
                $this->entityPreloader->preload($allChoices, 'character');
                $this->entityPreloader->preload($allChoices, 'application');
            }
        }

        return $applications;
    }

    /**
     * @param LarpApplication[] $applications
     */
    public function getDashboardStats(Larp $larp, array $applications = null): array
    {
        if ($applications === null) {
            $applications = $this->getApplicationsWithPreloading($larp);
        }

        $stats = [
            'total_applications' => count($applications),
            'pending_applications' => 0,
            'approved_applications' => 0,
            'rejected_applications' => 0,
            'total_choices' => 0,
        ];

        $characterChoices = [];
        $factionChoices = [];
        $charactersWithApplications = [];

        foreach ($applications as $application) {
            // Count application statuses
            if (method_exists($application, 'getStatus')) {
                $status = $application->getStatus();
                switch ($status) {
                    case 'pending':
                        $stats['pending_applications']++;
                        break;
                    case 'approved':
                        $stats['approved_applications']++;
                        break;
                    case 'rejected':
                        $stats['rejected_applications']++;
                        break;
                }
            }

            // Process choices
            foreach ($application->getChoices() as $choice) {
                $stats['total_choices']++;
                
                // Count character popularity
                $character = $choice->getCharacter();
                $characterId = $character->getId()->toRfc4122();
                
                if (!isset($characterChoices[$characterId])) {
                    $characterChoices[$characterId] = [
                        'character' => $character,
                        'count' => 0
                    ];
                }
                $characterChoices[$characterId]['count']++;
                $charactersWithApplications[$characterId] = $character;
                
                // Count faction distribution
                if ($character->getFactions()->count() > 0) {
                    foreach ($character->getFactions() as $faction) {
                        if (!isset($factionChoices[$faction->getTitle()])) {
                            $factionChoices[$faction->getTitle()] = [
                                'faction' => $faction,
                                'count' => 0
                            ];
                        }
                        $factionChoices[$faction->getTitle()]['count']++;
                    }
                }
            }
        }

        // Sort character stats by popularity (most wanted)
        uasort($characterChoices, fn ($a, $b): int => $b['count'] <=> $a['count']);
        $mostWantedCharacters = array_slice($characterChoices, 0, 10);
        $stats['most_wanted_characters'] = $mostWantedCharacters;

        // Get least wanted characters (exclude those in most wanted)
        $leastWantedCharacters = [];
        $mostWantedIds = array_keys($mostWantedCharacters);
        
        // First, add characters with applications but not in most wanted (sorted by lowest count)
        $sortedByLeastWanted = $characterChoices;
        uasort($sortedByLeastWanted, fn ($a, $b): int => $a['count'] <=> $b['count']);
        
        foreach ($sortedByLeastWanted as $characterId => $data) {
            if (!in_array($characterId, $mostWantedIds)) {
                $leastWantedCharacters[$characterId] = $data;
            }
        }
        
        // Then, add characters with no applications at all
        foreach ($larp->getCharacters() as $character) {
            $characterId = $character->getId()->toRfc4122();
            if (!isset($charactersWithApplications[$characterId])) {
                $leastWantedCharacters[$characterId] = [
                    'character' => $character,
                    'count' => 0
                ];
            }
        }
        
        $stats['least_wanted_characters'] = array_slice($leastWantedCharacters, 0, 10);

        // Calculate faction distribution with percentages for visual representation
        $totalFactionChoices = array_sum(array_column($factionChoices, 'count'));
        $factionDistribution = [];
        
        foreach ($factionChoices as $factionName => $data) {
            $percentage = $totalFactionChoices > 0 ? round(($data['count'] / $totalFactionChoices) * 100, 1) : 0;
            $factionDistribution[$factionName] = [
                'faction' => $data['faction'],
                'count' => $data['count'],
                'percentage' => $percentage
            ];
        }
        
        // Sort faction distribution by count (descending)
        uasort($factionDistribution, fn ($a, $b): int => $b['count'] <=> $a['count']);
        
        $stats['faction_distribution'] = $factionDistribution;

        return $stats;
    }
}
