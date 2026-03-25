<?php

declare(strict_types=1);

namespace App\Domain\Survey\Entity\Enum;

enum ApplicationMode: string
{
    case CHARACTER_SELECTION = 'character_selection';
    case SURVEY = 'survey';
    case TICKET_PURCHASE = 'ticket_purchase';

    public function getLabel(): string
    {
        return match ($this) {
            self::CHARACTER_SELECTION => 'Character Selection',
            self::SURVEY => 'Survey/Questionnaire',
            self::TICKET_PURCHASE => 'Open Ticket',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::CHARACTER_SELECTION => 'Players browse and choose characters to apply for',
            self::SURVEY => 'Players fill a questionnaire, organizers match them to characters',
            self::TICKET_PURCHASE => 'Anyone can buy a ticket — no character selection form, external payment handled outside the system',
        };
    }
}
