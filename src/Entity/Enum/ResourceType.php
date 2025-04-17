<?php

namespace App\Entity\Enum;

use App\Form\Integrations\CharacterDocDirectoryColumnMappingType;
use App\Form\Integrations\CharacterListColumnMappingType;
use App\Form\Integrations\EventListColumnMappingType;
use Symfony\Component\Form\AbstractType;

enum ResourceType: string
{
    case CHARACTER_LIST = 'character_list';
    case CHARACTER_DOC = 'character_doc';
    case CHARACTER_DOC_DIRECTORY = 'character_doc_directory';
    case EVENT_LIST = 'event_list';
    case EVENT_DOC = 'event_doc';

    /**
     * @return class-string<AbstractType>
     */
    public function getSubForm(): string
    {
        return match ($this) {
            self::CHARACTER_LIST => CharacterListColumnMappingType::class,
            self::EVENT_LIST => EventListColumnMappingType::class,
            self::CHARACTER_DOC_DIRECTORY => CharacterDocDirectoryColumnMappingType::class,
            self::CHARACTER_DOC => throw new \Exception('To be implemented'),
            self::EVENT_DOC => throw new \Exception('To be implemented'),
        };
    }

    public function matchesTargetType(TargetType $targetType): bool
    {
        return match ($this) {
            self::CHARACTER_LIST, self::CHARACTER_DOC => $targetType === TargetType::Character,
            self::EVENT_LIST, self::EVENT_DOC => $targetType === TargetType::Faction,
            default => false,
        };
    }

    public function isSpreadsheet(): bool
    {
        return in_array($this, [
            self::CHARACTER_LIST,
            self::EVENT_LIST,
            // future: EVENT_LIST etc.
        ], true);
    }

    public function isDocument(): bool
    {
        return in_array($this, [
            self::CHARACTER_DOC,
            self::EVENT_DOC,
            // future: EVENT_DOC etc.
        ], true);
    }

    public function isFolderMapping(): bool
    {
        return in_array($this, [
            self::CHARACTER_DOC_DIRECTORY,
            // other folder-related types
        ], true);
    }
}
