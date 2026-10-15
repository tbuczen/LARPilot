<?php

namespace App\Domain\Integrations\Service\Exceptions;

class UnsupportedCapability extends \LogicException
{
    public static function forProvider(string $providerKey, string $capability): self
    {
        return new self(sprintf('Integration provider "%s" does not support %s.', $providerKey, $capability));
    }
}
