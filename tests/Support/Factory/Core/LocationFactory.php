<?php

namespace Tests\Support\Factory\Core;

use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use App\Domain\Core\Entity\Location;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Location>
 */
final class LocationFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Location::class;
    }

    protected function defaults(): array
    {
        return [
            'title' => self::faker()->company(),
            'description' => self::faker()->paragraph(),
            'address' => self::faker()->streetAddress(),
            'city' => self::faker()->city(),
            'country' => self::faker()->country(),
            'postalCode' => self::faker()->postcode(),
            'latitude' => (string) self::faker()->latitude(),
            'longitude' => (string) self::faker()->longitude(),
            'capacity' => self::faker()->numberBetween(20, 500),
            'isActive' => true,
            'isPublic' => true,
            'approvalStatus' => LocationApprovalStatus::APPROVED,
        ];
    }

    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Location $location): void {})
        ;
    }

    // ========================================================================
    // Factory States
    // ========================================================================

    /**
     * Location with PENDING approval status
     */
    public function pending(): self
    {
        return $this->with([
            'approvalStatus' => LocationApprovalStatus::PENDING,
            'approvedBy' => null,
            'approvedAt' => null,
        ]);
    }

    /**
     * Location with APPROVED status
     */
    public function approved(): self
    {
        return $this->with([
            'approvalStatus' => LocationApprovalStatus::APPROVED,
            'approvedAt' => new \DateTimeImmutable(),
        ]);
    }

    /**
     * Location with REJECTED status
     */
    public function rejected(string $reason = 'Does not meet requirements'): self
    {
        return $this->with([
            'approvalStatus' => LocationApprovalStatus::REJECTED,
            'rejectionReason' => $reason,
            'approvedBy' => null,
            'approvedAt' => null,
        ]);
    }

    /**
     * Location approved by specific user
     */
    public function approvedBy(mixed $user): self
    {
        return $this->with([
            'approvalStatus' => LocationApprovalStatus::APPROVED,
            'approvedBy' => $user,
            'approvedAt' => new \DateTimeImmutable(),
        ]);
    }

    /**
     * Public location (visible to all)
     */
    public function public(): self
    {
        return $this->with([
            'isPublic' => true,
        ]);
    }

    /**
     * Private location (not publicly visible)
     */
    public function private(): self
    {
        return $this->with([
            'isPublic' => false,
        ]);
    }

    /**
     * Inactive location
     */
    public function inactive(): self
    {
        return $this->with([
            'isActive' => false,
        ]);
    }
}
