<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Integrations;

use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\ExternalSource;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ExternalSource>
 */
final class ExternalSourceFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return ExternalSource::class;
    }

    protected function defaults(): array
    {
        return [
            'integration' => LarpIntegrationFactory::new(),
            'kind' => ExternalSourceKind::GoogleFile,
            'externalId' => self::faker()->uuid(),
            'title' => self::faker()->words(3, true),
            'url' => self::faker()->url(),
        ];
    }
}
