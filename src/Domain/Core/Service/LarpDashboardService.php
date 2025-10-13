<?php

namespace App\Domain\Core\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;

readonly class LarpDashboardService
{
    public function __construct(
        private EntityPreloader $entityPreloader
    ) {
    }

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

        // Preload nested relationships to avoid N+1
        $participants = $larp->getParticipants()->toArray();
        if (!empty($participants)) {
            // Preload larpCharacters for all participants
            $this->entityPreloader->preload($participants, 'larpCharacters');

            // Get all characters from participants
            $characters = [];
            foreach ($participants as $participant) {
                foreach ($participant->getLarpCharacters() as $character) {
                    if ($character !== null) {
                        $characters[] = $character;
                    }
                }
            }

            // Preload factions for all characters
            if (!empty($characters)) {
                $this->entityPreloader->preload($characters, 'factions');
            }
        }

        // Preload faction members
        $factions = $larp->getFactions()->toArray();
        if (!empty($factions)) {
            $this->entityPreloader->preload($factions, 'members');
        }
    }

    private function getApplicationsData(Larp $larp): array
    {
        $applications = $larp->getApplications();
        $totalApplications = $applications->count();
        
        $pendingApplications = $applications->filter(fn ($app): bool => method_exists($app, 'getStatus') && $app->getStatus() === 'pending')->count();

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
        
        $assignedCharacters = $characters->filter(fn ($char): bool => method_exists($char, 'getAssignedTo') && $char->getAssignedTo() !== null)->count();

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
            $factionParticipants = $participants->filter(function (LarpParticipant $participant) use ($faction) {
                foreach ($participant->getLarpCharacters() as $larpCharacter) {
                    return $larpCharacter?->belongsToFaction($faction);
                }
            })->count();
            
            $factionStats[] = [
                'id' => $faction->getId()->toRfc4122(),
                'title' => $faction->getTitle() ?: 'Unknown',
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
        if ($larp->getStartDate() instanceof \DateTimeInterface) {
            $now = new \DateTime();
            $startDate = $larp->getStartDate();
            $interval = $now->diff($startDate);
            $daysUntilEvent = $interval->invert ? -$interval->days : $interval->days;
        }

        $eventDuration = null;
        if ($larp->getStartDate() instanceof \DateTimeInterface && $larp->getEndDate() instanceof \DateTimeInterface) {
            $interval = $larp->getStartDate()->diff($larp->getEndDate());
            $eventDuration = $interval->days;
        }

        return [
            'daysUntilEvent' => $daysUntilEvent,
            'duration' => $eventDuration,
        ];
    }
}
