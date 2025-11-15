<?php

namespace Tests\Support\Factory\Account;

use App\Domain\Account\Entity\Enum\UserStatus;
use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\Locale;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return User::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array
    {
        return [
            'username' => self::faker()->unique()->userName(),
            'contactEmail' => self::faker()->unique()->safeEmail(),
            'preferredLocale' => Locale::EN,
            'status' => UserStatus::APPROVED,
            'roles' => ['ROLE_USER'],
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(User $user): void {})
        ;
    }

    // ========================================================================
    // Factory States (following SOLID - Single Responsibility)
    // ========================================================================

    /**
     * User with PENDING status (awaiting approval)
     */
    public function pending(): self
    {
        return $this->with([
            'status' => UserStatus::PENDING,
        ]);
    }

    /**
     * User with APPROVED status (can access the platform)
     */
    public function approved(): self
    {
        return $this->with([
            'status' => UserStatus::APPROVED,
        ]);
    }

    /**
     * User with SUSPENDED status (temporarily blocked)
     */
    public function suspended(): self
    {
        return $this->with([
            'status' => UserStatus::SUSPENDED,
        ]);
    }

    /**
     * User with BANNED status (permanently blocked)
     */
    public function banned(): self
    {
        return $this->with([
            'status' => UserStatus::BANNED,
        ]);
    }

    /**
     * User with super admin role
     */
    public function superAdmin(): self
    {
        return $this->with([
            'status' => UserStatus::APPROVED,
            'roles' => ['ROLE_SUPER_ADMIN', 'ROLE_USER'],
        ]);
    }

    /**
     * User with a specific plan
     */
    public function withPlan(mixed $plan = null): self
    {
        if ($plan === null) {
            $plan = PlanFactory::new()->create();
        }

        return $this->with([
            'plan' => $plan,
        ]);
    }

    /**
     * User who organizes LARPs (has approved status and a plan)
     */
    public function organizer(): self
    {
        return $this->approved()->withPlan();
    }
}
