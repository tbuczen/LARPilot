<?php

namespace App\Domain\Integrations\Service\Sync;

use App\Domain\Integrations\Entity\ExternalSource;

final class SourceSyncIntervalPolicy
{
    public const CONFIG_KEY = 'sync_interval_minutes';
    public const DEFAULT_INTERVAL_MINUTES = 30;
    public const MINIMUM_INTERVAL_MINUTES = 5;

    public function nextSyncAt(ExternalSource $source, \DateTimeImmutable $now): \DateTimeImmutable
    {
        return $now->modify(sprintf('+%d minutes', $this->intervalMinutes($source)));
    }

    public function intervalMinutes(ExternalSource $source): int
    {
        $configuredMinutes = $source->getConfigValue(self::CONFIG_KEY);
        if (!is_numeric($configuredMinutes)) {
            return self::DEFAULT_INTERVAL_MINUTES;
        }

        return max(self::MINIMUM_INTERVAL_MINUTES, (int) $configuredMinutes);
    }
}
