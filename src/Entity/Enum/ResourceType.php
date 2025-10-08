<?php

namespace App\Entity\Enum;

use App\Form\Integrations\CharacterDocDirectoryMappingType;
use App\Form\Integrations\CharacterDocMappingType;
use App\Form\Integrations\CharacterListColumnMappingType;
use App\Form\Integrations\DocumentMetaFormType;
use App\Form\Integrations\EventDocMappingType;
use App\Form\Integrations\EventListColumnMappingType;
use App\Form\Integrations\SpreadsheetMetaFormType;
use Symfony\Component\Form\AbstractType;

enum ResourceType: string
{
    case CHARACTER_LIST = 'character_list';
    case CHARACTER_DOC = 'character_doc';
    case CHARACTER_DOC_DIRECTORY = 'character_doc_directory';
    case EVENT_LIST = 'event_list';
    case EVENT_DOC = 'event_doc';

    /**
     * @return class-string<AbstractType>|null
     */
    public function getSubForm(): ?string
    {
        return match ($this) {
            self::CHARACTER_LIST => CharacterListColumnMappingType::class,
            self::EVENT_LIST => EventListColumnMappingType::class,
            self::CHARACTER_DOC_DIRECTORY => CharacterDocDirectoryMappingType::class,
            self::CHARACTER_DOC => CharacterDocMappingType::class,
            self::EVENT_DOC => EventDocMappingType::class,
        };
    }

    /**
     * @return class-string<AbstractType>|null
     */
    public function getMetaForm(): ?string
    {
        return match ($this) {
            self::CHARACTER_LIST, self::EVENT_LIST => SpreadsheetMetaFormType::class,
            self::CHARACTER_DOC, self::EVENT_DOC => DocumentMetaFormType::class,
            default => null,
            // etc.
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
        return $this === self::CHARACTER_DOC_DIRECTORY;
    }
}
