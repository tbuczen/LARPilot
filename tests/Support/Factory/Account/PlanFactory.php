<?php

namespace Tests\Support\Factory\Account;

use App\Domain\Account\Entity\Plan;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Plan>
 */
final class PlanFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Plan::class;
    }

    protected function defaults(): array
    {
        $suffix = uniqid('', true);
        return [
            'name' => 'Plan_' . $suffix,
            'description' => self::faker()->sentence(),
            'maxLarps' => 3,
            'maxParticipantsPerLarp' => 50,
            'storageLimitMb' => 500,
            'hasGoogleIntegrations' => false,
            'hasCustomBranding' => false,
            'priceInCents' => 0,
            'isFree' => true,
            'isActive' => true,
            'sortOrder' => 0,
        ];
    }

    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Plan $plan): void {})
        ;
    }

    // ========================================================================
    // Factory States
    // ========================================================================

    /**
     * Free plan with basic features
     */
    public function free(): self
    {
        return $this->with([
            'name' => 'Free_' . uniqid('', true),
            'maxLarps' => 1,
            'maxParticipantsPerLarp' => 20,
            'storageLimitMb' => 100,
            'hasGoogleIntegrations' => false,
            'hasCustomBranding' => false,
            'priceInCents' => 0,
            'isFree' => true,
            'sortOrder' => 0,
        ]);
    }

    /**
     * Unlimited plan with all features
     */
    public function unlimited(): self
    {
        return $this->with([
            'name' => 'Unlimited_' . uniqid('', true),
            'maxLarps' => null,
            'maxParticipantsPerLarp' => null,
            'storageLimitMb' => null,
            'hasGoogleIntegrations' => true,
            'hasCustomBranding' => true,
            'priceInCents' => 9999,
            'isFree' => false,
            'sortOrder' => 30,
        ]);
    }

    /**
     * Premium plan with enhanced features
     */
    public function premium(): self
    {
        return $this->with([
            'name' => 'Premium_' . uniqid('', true),
            'maxLarps' => 10,
            'maxParticipantsPerLarp' => 200,
            'storageLimitMb' => 5000,
            'hasGoogleIntegrations' => true,
            'hasCustomBranding' => true,
            'priceInCents' => 4999,
            'isFree' => false,
            'sortOrder' => 20,
        ]);
    }

    /**
     * Basic paid plan
     */
    public function basic(): self
    {
        return $this->with([
            'name' => 'Basic_' . uniqid('', true),
            'maxLarps' => 3,
            'maxParticipantsPerLarp' => 50,
            'storageLimitMb' => 1000,
            'hasGoogleIntegrations' => false,
            'hasCustomBranding' => false,
            'priceInCents' => 1999,
            'isFree' => false,
            'sortOrder' => 10,
        ]);
    }
}
