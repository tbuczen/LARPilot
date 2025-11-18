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
            self::CHARACTER_SELECTION => 'Character Selection',
            self::SURVEY => 'Survey/Questionnaire',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::CHARACTER_SELECTION => 'Players browse and choose characters to apply for',
            self::SURVEY => 'Players fill a questionnaire, organizers match them to characters',
        };
    }
}
