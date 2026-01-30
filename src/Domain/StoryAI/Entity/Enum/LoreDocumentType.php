<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Entity\Enum;

/**
 * Types of lore documents that can be uploaded.
 */
enum LoreDocumentType: string
{
    case SETTING_OVERVIEW = 'setting_overview';
    case WORLD_HISTORY = 'world_history';
    case MAGIC_RULES = 'magic_rules';
    case TECHNOLOGY_RULES = 'technology_rules';
    case CULTURE_NOTES = 'culture_notes';
    case GEOGRAPHY = 'geography';
    case POLITICS = 'politics';
    case RELIGION = 'religion';
    case ECONOMICS = 'economics';
    case GENERAL = 'general';

    public function getLabel(): string
    {
        return match ($this) {
            self::SETTING_OVERVIEW => 'Setting Overview',
            self::WORLD_HISTORY => 'World History',
            self::MAGIC_RULES => 'Magic/Power Rules',
            self::TECHNOLOGY_RULES => 'Technology Rules',
            self::CULTURE_NOTES => 'Culture Notes',
            self::GEOGRAPHY => 'Geography',
            self::POLITICS => 'Politics & Governance',
            self::RELIGION => 'Religion & Beliefs',
            self::ECONOMICS => 'Economics & Trade',
            self::GENERAL => 'General',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SETTING_OVERVIEW => 'High-level overview of the game world and setting',
            self::WORLD_HISTORY => 'Historical events, timeline, and past eras',
            self::MAGIC_RULES => 'Rules and lore about magic, powers, or supernatural abilities',
            self::TECHNOLOGY_RULES => 'Available technology level and special tech rules',
            self::CULTURE_NOTES => 'Cultural practices, customs, and social norms',
            self::GEOGRAPHY => 'Maps, locations, regions, and physical world details',
            self::POLITICS => 'Political structures, factions, and governance',
            self::RELIGION => 'Religious systems, deities, and spiritual beliefs',
            self::ECONOMICS => 'Trade, currency, resources, and economic systems',
            self::GENERAL => 'Other lore and setting information',
        };
    }
}
