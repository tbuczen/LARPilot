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
        $suffix = uniqid('', true);
        return [
            'username' => self::faker()->userName() . '_' . $suffix,
            'contactEmail' => 'user_' . $suffix . '@test.local',
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

    public function organizer(): self
    {
        return $this->approved()->withPlan();
    }

    public static function createPendingUser(): User
    {
        return UserFactory::new()->pending()->create()->_real();
    }

    public static function createSuperAdmin(): User
    {
        return UserFactory::new()->approved()->superAdmin()->create()->_real();
    }

    public static function createApprovedUser(): User
    {
        return UserFactory::new()->approved()->create()->_real();
    }

    public static function createSuspendedUser(): User
    {
        return UserFactory::new()->suspended()->create()->_real();
    }

    public static function createBannedUser(): User
    {
        return UserFactory::new()->banned()->create()->_real();
    }
}
