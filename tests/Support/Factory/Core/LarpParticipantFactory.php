<?php

namespace Tests\Support\Factory\Core;

use App\Domain\Core\Entity\LarpParticipant;
use Tests\Support\Factory\Account\UserFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<LarpParticipant>
 */
final class LarpParticipantFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return LarpParticipant::class;
    }

    protected function defaults(): array
    {
        return [
            'user' => UserFactory::new()->approved(),
            'larp' => LarpFactory::new(),
            'roles' => ['ROLE_PLAYER'],
        ];
    }

    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(LarpParticipant $larpParticipant): void {})
        ;
    }

    // ========================================================================
    // Factory States
    // ========================================================================

    /**
     * Participant with PLAYER role
     */
    public function player(): self
    {
        return $this->with([
            'roles' => ['ROLE_PLAYER'],
        ]);
    }

    /**
     * Participant with ORGANIZER role (can manage LARP)
     */
    public function organizer(): self
    {
        return $this->with([
            'roles' => ['ROLE_ORGANIZER'],
        ]);
    }

    /**
     * Participant with ADMIN role
     */
    public function admin(): self
    {
        return $this->with([
            'roles' => ['ROLE_ADMIN'],
        ]);
    }

    /**
     * Participant with STORY_WRITER role
     */
    public function storyWriter(): self
    {
        return $this->with([
            'roles' => ['ROLE_STORY_WRITER'],
        ]);
    }

    /**
     * Participant with PHOTOGRAPHER role
     */
    public function photographer(): self
    {
        return $this->with([
            'roles' => ['ROLE_PHOTOGRAPHER'],
        ]);
    }

    /**
     * Participant with TRUST_PERSON role
     */
    public function trustPerson(): self
    {
        return $this->with([
            'roles' => ['ROLE_TRUST_PERSON'],
        ]);
    }

    /**
     * Participant for a specific LARP
     */
    public function forLarp(mixed $larp): self
    {
        return $this->with([
            'larp' => $larp,
        ]);
    }

    /**
     * Participant for a specific User
     */
    public function forUser(mixed $user): self
    {
        return $this->with([
            'user' => $user,
        ]);
    }

    /**
     * Participant with multiple roles
     */
    public function withRoles(array $roles): self
    {
        return $this->with([
            'roles' => $roles,
        ]);
    }
}
