<?php

namespace App\Domain\Integrations\Event;

use App\Domain\Integrations\Entity\ExternalReference;
use Symfony\Contracts\EventDispatcher\Event;

final class ExternalReferenceLinked extends Event
{
    public function __construct(
        private readonly ExternalReference $reference,
    ) {
    }

    public function getReference(): ExternalReference
    {
        return $this->reference;
    }
}
