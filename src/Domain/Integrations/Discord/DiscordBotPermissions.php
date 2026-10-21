<?php

namespace App\Domain\Integrations\Discord;

use App\Domain\Integrations\Entity\LarpIntegration;

final class DiscordBotPermissions
{
    public const VIEW_CHANNEL = 1 << 10;
    public const SEND_MESSAGES = 1 << 11;
    public const READ_MESSAGE_HISTORY = 1 << 16;
    public const CREATE_PUBLIC_THREADS = 1 << 35;
    public const SEND_MESSAGES_IN_THREADS = 1 << 38;
    public const ADMINISTRATOR = 1 << 3;

    public const READ = self::VIEW_CHANNEL | self::READ_MESSAGE_HISTORY;
    public const POST = self::SEND_MESSAGES | self::CREATE_PUBLIC_THREADS | self::SEND_MESSAGES_IN_THREADS;
    public const REQUESTED = self::READ | self::POST;

    public const GRANTED_CONFIG_KEY = 'botPermissions';
    public const POSTING_DENIED_CONFIG_KEY = 'postingDenied';

    public static function canPost(LarpIntegration $integration): bool
    {
        $config = $integration->getConfig() ?? [];
        if (($config[self::POSTING_DENIED_CONFIG_KEY] ?? false) === true) {
            return false;
        }

        $granted = $config[self::GRANTED_CONFIG_KEY] ?? null;
        if (!is_numeric($granted)) {
            return false;
        }

        $granted = (int) $granted;

        return ($granted & self::ADMINISTRATOR) !== 0 || ($granted & self::POST) === self::POST;
    }

    public static function grantedFromCallback(mixed $permissions): ?string
    {
        return is_string($permissions) && ctype_digit($permissions) ? $permissions : null;
    }

    public static function markPostingDenied(LarpIntegration $integration): void
    {
        $integration->setConfig(array_merge($integration->getConfig() ?? [], [self::POSTING_DENIED_CONFIG_KEY => true]));
    }
}
