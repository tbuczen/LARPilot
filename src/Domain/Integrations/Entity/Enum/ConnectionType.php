<?php

namespace App\Domain\Integrations\Entity\Enum;

enum ConnectionType: string
{
    case UserOauth = 'user_oauth';
    case BotInstall = 'bot_install';

    public function requiresUserToken(): bool
    {
        return $this === self::UserOauth;
    }
}
