<?php

declare(strict_types=1);

namespace App\Domain\Core\Entity\Enum;

enum LarpModule: string
{
    case STORY = 'story';
    case KANBAN = 'kanban';
    case MAP = 'map';
    case INCIDENT = 'incident';
    case GALLERY = 'gallery';
    case MAILING = 'mailing';
    case APPLICATION_MATCHER = 'application_matcher';
    case AI_AGENT = 'ai_agent';

    public function getLabel(): string
    {
        return match ($this) {
            self::STORY => 'Story system (Characters, Threads, Quests, Events, Factions)',
            self::KANBAN => 'Kanban board (task management for organizers)',
            self::MAP => 'Map system (locations and interactive world map)',
            self::INCIDENT => 'Incident reporting (safety logging during the event)',
            self::GALLERY => 'Photo galleries',
            self::MAILING => 'Mailing / email templates',
            self::APPLICATION_MATCHER => 'Application matcher (automatic character matching)',
            self::AI_AGENT => 'AI assistant',
        };
    }
}
