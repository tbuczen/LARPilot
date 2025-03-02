<?php

namespace App\Enum;

enum LarpIntegrationProvider: string
{
    case Facebook = 'facebook';
    case Google = 'integration_google_drive';
    case Discord = 'discord';
    case Asana = 'asana';
    case Trello = 'trello';
    case Miro = 'miro';
}
