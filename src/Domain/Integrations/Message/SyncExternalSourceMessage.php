<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Message;

use Symfony\Component\Uid\Uuid;

final readonly class SyncExternalSourceMessage
{
    public function __construct(
        public Uuid $sourceId,
    ) {
    }
}
