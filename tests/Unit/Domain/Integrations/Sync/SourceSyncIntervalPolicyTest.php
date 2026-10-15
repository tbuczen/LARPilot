<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Sync;

use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Service\Sync\SourceSyncIntervalPolicy;
use PHPUnit\Framework\TestCase;

class SourceSyncIntervalPolicyTest extends TestCase
{
    /**
     * @return iterable<string, array{array<string, mixed>, int}>
     */
    public static function intervalCases(): iterable
    {
        yield 'default without config' => [[], SourceSyncIntervalPolicy::DEFAULT_INTERVAL_MINUTES];
        yield 'configured interval' => [[SourceSyncIntervalPolicy::CONFIG_KEY => 120], 120];
        yield 'numeric string' => [[SourceSyncIntervalPolicy::CONFIG_KEY => '45'], 45];
        yield 'clamped to minimum' => [[SourceSyncIntervalPolicy::CONFIG_KEY => 1], SourceSyncIntervalPolicy::MINIMUM_INTERVAL_MINUTES];
        yield 'invalid value falls back to default' => [[SourceSyncIntervalPolicy::CONFIG_KEY => 'hourly'], SourceSyncIntervalPolicy::DEFAULT_INTERVAL_MINUTES];
    }

    /**
     * @dataProvider intervalCases
     * @param array<string, mixed> $config
     */
    public function testNextSyncAtAddsTheSourceInterval(array $config, int $expectedMinutes): void
    {
        $source = (new ExternalSource())->setConfig($config);
        $now = new \DateTimeImmutable('2026-10-12 12:00:00');

        $nextSyncAt = (new SourceSyncIntervalPolicy())->nextSyncAt($source, $now);

        $this->assertEquals($now->modify(sprintf('+%d minutes', $expectedMinutes)), $nextSyncAt);
    }
}
