<?php

namespace App\Domain\Core\Event;

use Symfony\Component\Uid\Uuid;

readonly class LarpDeletedEvent
{
    public function __construct(
        private Uuid $larpId,
    ) {
    }

    public function getLarpId(): Uuid
    {
        return $this->larpId;
    }
}
