<?php

namespace App\Service\Larp;

use App\Entity\Larp;
use App\Repository\LarpApplicationRepository;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;

class LarpApplicationDashboardService
{
    public function __construct(
        private EntityPreloader $entityPreloader,
        private LarpApplicationRepository $applicationRepository
    ) {}

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
            
            if (!empty($allChoices)) {
                $this->entityPreloader->preload($allChoices, 'character');
                $this->entityPreloader->preload($allChoices, 'application');
            }
        }

        return $applications;
    }

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
            'priority_distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
        ];

        $characterChoices = [];
        $factionChoices = [];

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
                $characterId = $choice->getCharacter()->getId()->toRfc4122();
                $characterChoices[$characterId] = ($characterChoices[$characterId] ?? 0) + 1;
                
                // Count faction distribution
                $character = $choice->getCharacter();
                if ($character->getFactions()->count() > 0) {
                    foreach ($character->getFactions() as $faction) {
                        $factionName = method_exists($faction, 'getName') ? $faction->getName() : 'Unknown';
                        $factionChoices[$factionName] = ($factionChoices[$factionName] ?? 0) + 1;
                    }
                }
                
                // Count priority distribution
                if (method_exists($choice, 'getPriority')) {
                    $priority = $choice->getPriority();
                    if ($priority >= 1 && $priority <= 5) {
                        $stats['priority_distribution'][$priority]++;
                    }
                }
            }
        }

        // Sort character stats by popularity
        arsort($characterChoices);
        $stats['character_stats'] = array_slice($characterChoices, 0, 10); // Top 10 most wanted characters

        // Sort faction distribution
        arsort($factionChoices);
        $stats['faction_distribution'] = $factionChoices;

        return $stats;
    }
}