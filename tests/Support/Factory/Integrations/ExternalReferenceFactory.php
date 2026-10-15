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
        $externalId = self::faker()->regexify('[A-Za-z0-9_-]{44}');

        return [
            'provider' => LarpIntegrationProvider::Google,
            'externalId' => $externalId,
            'name' => self::faker()->words(3, true),
            'url' => 'https://docs.google.com/document/d/' . $externalId . '/edit',
            'referenceType' => ReferenceType::Document,
            'role' => ReferenceRole::Primary,
            'storyObject' => CharacterFactory::new(),
            'createdBy' => UserFactory::new(),
        ];
    }

    public function forStoryObject(mixed $storyObject): self
    {
        return $this->with([
            'storyObject' => $storyObject,
        ]);
    }

    public function createdBy(mixed $user): self
    {
        return $this->with([
            'createdBy' => $user,
        ]);
    }

    public function withProvider(LarpIntegrationProvider $provider): self
    {
        return $this->with([
            'provider' => $provider,
        ]);
    }

    public function withReferenceType(ReferenceType $referenceType): self
    {
        return $this->with([
            'referenceType' => $referenceType,
        ]);
    }

    public function withRole(ReferenceRole $role): self
    {
        return $this->with([
            'role' => $role,
        ]);
    }

    public function url(string $url): self
    {
        return $this->with([
            'externalId' => $url,
            'url' => $url,
            'referenceType' => ReferenceType::Url,
            'role' => ReferenceRole::Mention,
        ]);
    }
}
