<?php

declare(strict_types=1);

namespace App\Domain\Core\Service\Monitoring;

use function Sentry\init;

final class BootErrorReporter
{
    /**
     * @param array<string, mixed> $context
     */
    public static function register(array $context): void
    {
        $dsn = (string) ($context['SENTRY_DSN'] ?? '');
        if ($dsn === '' || ($context['APP_ENV'] ?? null) !== 'prod') {
            return;
        }

        $release = (string) ($context['SENTRY_RELEASE'] ?? '');

        init([
            'dsn' => $dsn,
            'environment' => 'prod',
            'release' => $release === '' ? null : $release,
            'send_default_pii' => false,
        ]);
    }
}
