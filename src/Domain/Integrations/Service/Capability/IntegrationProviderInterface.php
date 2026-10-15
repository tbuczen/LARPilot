<?php

namespace App\Domain\Integrations\Service\Capability;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface IntegrationProviderInterface
{
    public static function providerKey(): string;
}
