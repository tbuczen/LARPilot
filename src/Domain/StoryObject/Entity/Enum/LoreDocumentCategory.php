<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Entity\Enum;

/**
 * Categories for general lore documents.
 */
enum LoreDocumentCategory: string
{
    case WORLD_SETTING = 'world_setting';
    case TIMELINE = 'timeline';
    case RELIGION = 'religion';
    case MAGIC_SYSTEM = 'magic_system';
    case CULTURE = 'culture';
    case GEOGRAPHY = 'geography';
    case POLITICS = 'politics';
    case ECONOMICS = 'economics';
    case HISTORY = 'history';
    case RULES = 'rules';
    case GENERAL = 'general';

    public function getLabel(): string
    {
        return match ($this) {
            self::WORLD_SETTING => 'World Setting',
            self::TIMELINE => 'Timeline',
            self::RELIGION => 'Religion',
            self::MAGIC_SYSTEM => 'Magic System',
            self::CULTURE => 'Culture',
            self::GEOGRAPHY => 'Geography',
            self::POLITICS => 'Politics',
            self::ECONOMICS => 'Economics',
            self::HISTORY => 'History',
            self::RULES => 'Rules & Mechanics',
            self::GENERAL => 'General',
        };
    }
}
