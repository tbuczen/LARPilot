<?php

namespace App\Domain\Integrations\Entity\Enum;

use App\Domain\Integrations\Form\Integrations\CharacterDocDirectoryMappingType;
use App\Domain\Integrations\Form\Integrations\CharacterDocMappingType;
use App\Domain\Integrations\Form\Integrations\CharacterListColumnMappingType;
use App\Domain\Integrations\Form\Integrations\DocumentMetaFormType;
use App\Domain\Integrations\Form\Integrations\EventDocMappingType;
use App\Domain\Integrations\Form\Integrations\EventListColumnMappingType;
use App\Domain\Integrations\Form\Integrations\SpreadsheetMetaFormType;
use App\Domain\Integrations\Form\Integrations\TagListColumnMappingType;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use Symfony\Component\Form\AbstractType;

enum ResourceType: string
{
    case CHARACTER_LIST = 'character_list';
    case CHARACTER_DOC = 'character_doc';
    case CHARACTER_DOC_DIRECTORY = 'character_doc_directory';
    case CHARACTER_DOC_TEMPLATE = 'character_doc_template';
    case EVENT_LIST = 'event_list';
    case EVENT_DOC = 'event_doc';
    case TAG_LIST = 'tag_list';

    /**
     * @return class-string<AbstractType>|null
     */
    public function getSubForm(): ?string
    {
        return match ($this) {
            self::CHARACTER_LIST => CharacterListColumnMappingType::class,
            self::EVENT_LIST => EventListColumnMappingType::class,
            self::TAG_LIST => TagListColumnMappingType::class,
            self::CHARACTER_DOC_DIRECTORY => CharacterDocDirectoryMappingType::class,
            self::CHARACTER_DOC_TEMPLATE, self::CHARACTER_DOC => CharacterDocMappingType::class,
            self::EVENT_DOC => EventDocMappingType::class,
        };
    }

    /**
     * @return class-string<AbstractType>|null
     */
    public function getMetaForm(): ?string
    {
        return match ($this) {
            self::CHARACTER_LIST, self::EVENT_LIST, self::TAG_LIST => SpreadsheetMetaFormType::class,
            self::CHARACTER_DOC, self::CHARACTER_DOC_TEMPLATE, self::EVENT_DOC => DocumentMetaFormType::class,
            default => null,
            // etc.
        };
    }

    public function matchesTargetType(TargetType $targetType): bool
    {
        return match ($this) {
            self::CHARACTER_LIST, self::CHARACTER_DOC, self::CHARACTER_DOC_TEMPLATE, self::CHARACTER_DOC_DIRECTORY => $targetType === TargetType::Character,
            self::EVENT_LIST, self::EVENT_DOC => $targetType === TargetType::Faction,
            self::TAG_LIST => $targetType === TargetType::Tag,
            default => false,
        };
    }

    public function isSpreadsheet(): bool
    {
        return in_array($this, [
            self::CHARACTER_LIST,
            self::EVENT_LIST,
            self::TAG_LIST,
            // future: other list types
        ], true);
    }

    public function isDocument(): bool
    {
        return in_array($this, [
            self::CHARACTER_DOC,
            self::CHARACTER_DOC_TEMPLATE,
            self::EVENT_DOC,
            // future: EVENT_DOC etc.
        ], true);
    }

    public function isFolderMapping(): bool
    {
        return $this === self::CHARACTER_DOC_DIRECTORY;
    }
}
