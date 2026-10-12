<?php

namespace App\Domain\Integrations\Entity\Enum;

enum ExternalSourceKind: string
{
    case DiscordChannel = 'discord_channel';
    case DiscordForum = 'discord_forum';
    case MiroBoard = 'miro_board';
    case GoogleFile = 'google_file';
}
