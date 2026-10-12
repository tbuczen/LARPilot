<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Integrations;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<LarpIntegration>
 */
final class LarpIntegrationFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return LarpIntegration::class;
    }

    protected function defaults(): array
    {
        return [
            'provider' => LarpIntegrationProvider::Google,
            'accessToken' => self::faker()->sha256(),
            'expiresAt' => new \DateTime('+1 hour'),
            'larp' => LarpFactory::new(),
            'createdBy' => UserFactory::new(),
        ];
    }
}
