<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Integrations;

use App\Domain\Integrations\Entity\Enum\ConnectionType;
use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use Doctrine\Common\Collections\ArrayCollection;
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
            'connectionType' => ConnectionType::UserOauth,
            'status' => IntegrationStatus::Active,
            'accessToken' => self::faker()->sha256(),
            'refreshToken' => self::faker()->sha256(),
            'expiresAt' => new \DateTime('+1 hour'),
            'scopes' => 'https://www.googleapis.com/auth/drive.file',
            'owner' => self::faker()->safeEmail(),
            'larp' => LarpFactory::new(),
            'createdBy' => UserFactory::new(),
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (LarpIntegration $integration): void {
            $sharedFiles = new \ReflectionProperty(LarpIntegration::class, 'sharedFiles');
            if ($sharedFiles->isInitialized($integration)) {
                return;
            }
            $sharedFiles->setValue($integration, new ArrayCollection());
        });
    }

    public function forLarp(mixed $larp): self
    {
        return $this->with([
            'larp' => $larp,
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

    public function google(): self
    {
        return $this->withProvider(LarpIntegrationProvider::Google);
    }

    public function facebook(): self
    {
        return $this->withProvider(LarpIntegrationProvider::Facebook);
    }

    public function expired(): self
    {
        return $this->with([
            'expiresAt' => new \DateTime('-1 day'),
        ]);
    }

    public function withoutRefreshToken(): self
    {
        return $this->with([
            'refreshToken' => null,
        ]);
    }

    public function botInstall(string $externalAccountId): self
    {
        return $this->with([
            'connectionType' => ConnectionType::BotInstall,
            'externalAccountId' => $externalAccountId,
            'accessToken' => null,
            'refreshToken' => null,
            'expiresAt' => null,
            'scopes' => null,
            'owner' => null,
        ]);
    }

    public function needsReauth(string $lastError = 'Token has been expired or revoked.'): self
    {
        return $this->with([
            'status' => IntegrationStatus::NeedsReauth,
            'lastError' => $lastError,
        ]);
    }
}
