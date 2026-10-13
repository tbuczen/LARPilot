<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Schedule;

use App\Domain\Integrations\Message\EnqueueDueSourceSyncsMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule(self::NAME)]
final class IntegrationSyncSchedule implements ScheduleProviderInterface
{
    public const NAME = 'integrations';
    public const FREQUENCY = '5 minutes';

    private ?Schedule $schedule = null;

    public function __construct(
        #[Autowire(service: 'cache.scheduler')]
        private readonly CacheInterface $schedulerCache,
        private readonly LockFactory $lockFactory,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return $this->schedule ??= (new Schedule())
            ->add(RecurringMessage::every(self::FREQUENCY, new EnqueueDueSourceSyncsMessage()))
            ->stateful($this->schedulerCache)
            ->processOnlyLastMissedRun(true)
            ->lock($this->lockFactory->createLock('integrations.sync_schedule'));
    }
}
