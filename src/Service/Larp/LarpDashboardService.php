<?php

namespace App\Service\Larp;

use App\Entity\Larp;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;

class LarpDashboardService
{
    public function __construct(
        private EntityPreloader $entityPreloader
    ) {}

    public function getDashboardData(Larp $larp): array
    {
        // Preload all related entities to avoid N+1 queries
        $this->preloadLarpData($larp);

        return [
            'applications' => $this->getApplicationsData($larp),
            'characters' => $this->getCharactersData($larp),
            'participants' => $this->getParticipantsData($larp),
            'factions' => $this->getFactionsData($larp),
            'skills' => $this->getSkillsData($larp),
            'events' => $this->getEventsData($larp),
            'integrations' => $this->getIntegrationsData($larp),
            'timing' => $this->getTimingData($larp),
        ];
    }

    private function preloadLarpData(Larp $larp): void
    {
        // Preload each collection property separately
        $this->entityPreloader->preload([$larp], 'applications');
        $this->entityPreloader->preload([$larp], 'larpParticipants');
        $this->entityPreloader->preload([$larp], 'characters');
        $this->entityPreloader->preload([$larp], 'factions');
        $this->entityPreloader->preload([$larp], 'skills');
        $this->entityPreloader->preload([$larp], 'events');
        $this->entityPreloader->preload([$larp], 'integrations');
    }

    private function getApplicationsData(Larp $larp): array
    {
        $applications = $larp->getApplications();
        $totalApplications = $applications->count();
        
        $pendingApplications = $applications->filter(function($app) {
            return method_exists($app, 'getStatus') && $app->getStatus() === 'pending';
        })->count();

        $participants = $larp->getParticipants();
        $approvedApplications = $participants->count();

        return [
            'total' => $totalApplications,
            'pending' => $pendingApplications,
            'approved' => $approvedApplications,
        ];
    }

    private function getCharactersData(Larp $larp): array
    {
        $characters = $larp->getCharacters();
        $totalCharacters = $characters->count();
        
        $assignedCharacters = $characters->filter(function($char) {
            return method_exists($char, 'getAssignedTo') && $char->getAssignedTo() !== null;
        })->count();

        return [
            'total' => $totalCharacters,
            'assigned' => $assignedCharacters,
            'unassigned' => $totalCharacters - $assignedCharacters,
        ];
    }

    private function getParticipantsData(Larp $larp): array
    {
        $participants = $larp->getParticipants();
        
        return [
            'total' => $participants->count(),
            'maxChoices' => $larp->getMaxCharacterChoices(),
        ];
    }

    private function getFactionsData(Larp $larp): array
    {
        $factions = $larp->getFactions();
        $participants = $larp->getParticipants();
        
        $factionStats = [];
        foreach ($factions as $faction) {
            $factionParticipants = $participants->filter(function($participant) use ($faction) {
                return method_exists($participant, 'getFaction') && $participant->getFaction() === $faction;
            })->count();
            
            $factionStats[] = [
                'name' => method_exists($faction, 'getName') ? $faction->getName() : 'Unknown',
                'participants' => $factionParticipants,
            ];
        }

        return [
            'total' => $factions->count(),
            'stats' => $factionStats,
        ];
    }

    private function getSkillsData(Larp $larp): array
    {
        return [
            'total' => $larp->getSkills()->count(),
        ];
    }

    private function getEventsData(Larp $larp): array
    {
        return [
            'total' => $larp->getEvents()->count(),
        ];
    }

    private function getIntegrationsData(Larp $larp): array
    {
        return [
            'total' => $larp->getIntegrations()->count(),
        ];
    }

    private function getTimingData(Larp $larp): array
    {
        $daysUntilEvent = null;
        if ($larp->getStartDate()) {
            $now = new \DateTime();
            $startDate = $larp->getStartDate();
            $interval = $now->diff($startDate);
            $daysUntilEvent = $interval->invert ? -$interval->days : $interval->days;
        }

        $eventDuration = null;
        if ($larp->getStartDate() && $larp->getEndDate()) {
            $interval = $larp->getStartDate()->diff($larp->getEndDate());
            $eventDuration = $interval->days;
        }

        return [
            'daysUntilEvent' => $daysUntilEvent,
            'duration' => $eventDuration,
        ];
    }
}