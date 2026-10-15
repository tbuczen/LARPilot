<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client;

final class UsleepDiscordSleeper implements DiscordSleeper
{
    public function sleep(float $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }

        usleep((int) ceil($seconds * 1_000_000));
    }
}
