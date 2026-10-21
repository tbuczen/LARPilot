<?php

declare(strict_types=1);

namespace App\Domain\Survey\Entity\Enum;

enum ApplicationMode: string
{
    case CHARACTER_SELECTION = 'character_selection';
    case SURVEY = 'survey';

    public function getLabel(): string
    {
        return match ($this) {
            self::CHARACTER_SELECTION => 'enum.application_mode.character_selection',
            self::SURVEY => 'enum.application_mode.survey',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::CHARACTER_SELECTION => 'enum.application_mode.character_selection_description',
            self::SURVEY => 'enum.application_mode.survey_description',
        };
    }
}
