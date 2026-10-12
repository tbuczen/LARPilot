<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Integrations;

use App\Domain\Integrations\Entity\Enum\ConnectionType;
use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
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
            'larp' => LarpFactory::new(),
            'createdBy' => UserFactory::new()->approved(),
            'provider' => LarpIntegrationProvider::Google,
            'connectionType' => ConnectionType::UserOauth,
            'status' => IntegrationStatus::Active,
            'accessToken' => self::faker()->sha256(),
            'refreshToken' => self::faker()->sha256(),
            'expiresAt' => new \DateTimeImmutable('+1 hour'),
        ];
    }

    public function forLarp(mixed $larp): self
    {
        return $this->with(['larp' => $larp]);
    }

    public function createdBy(mixed $user): self
    {
        return $this->with(['createdBy' => $user]);
    }

    public function forProvider(LarpIntegrationProvider $provider): self
    {
        return $this->with(['provider' => $provider]);
    }

    public function botInstall(string $externalAccountId): self
    {
        return $this->with([
            'connectionType' => ConnectionType::BotInstall,
            'externalAccountId' => $externalAccountId,
            'accessToken' => null,
            'refreshToken' => null,
            'expiresAt' => null,
        ]);
    }

    public function expired(): self
    {
        return $this->with(['expiresAt' => new \DateTimeImmutable('-1 hour')]);
    }
}
