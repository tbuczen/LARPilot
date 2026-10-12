<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Integrations;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\ExternalReference;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ExternalReference>
 */
final class ExternalReferenceFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return ExternalReference::class;
    }

    protected function defaults(): array
    {
        return [
            'storyObject' => CharacterFactory::new(),
            'provider' => LarpIntegrationProvider::Google,
            'externalId' => self::faker()->uuid(),
            'referenceType' => ReferenceType::Url,
            'role' => ReferenceRole::Mention,
            'name' => self::faker()->sentence(3),
            'url' => self::faker()->url(),
            'createdBy' => UserFactory::new(),
        ];
    }
}
