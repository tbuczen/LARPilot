<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client;

interface DiscordSleeper
{
    public function sleep(float $seconds): void;
}
