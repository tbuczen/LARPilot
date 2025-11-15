<?php

namespace Tests\Support\Factory\Core;

use App\Domain\Core\Entity\Larp;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Larp>
 */
final class LarpFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Larp::class;
    }

    protected function defaults(): array
    {
        return [
            'title' => self::faker()->words(3, true),
            'description' => self::faker()->paragraph(),
            'startDate' => self::faker()->dateTimeBetween('+1 month', '+6 months'),
            'endDate' => self::faker()->dateTimeBetween('+6 months', '+1 year'),
            'maxCharacterChoices' => 3,
            'minThreadsPerCharacter' => 2,
            'marking' => 'DRAFT',
        ];
    }

    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Larp $larp): void {})
        ;
    }

    // ========================================================================
    // Factory States (Workflow states)
    // ========================================================================

    /**
     * LARP in DRAFT state (initial state)
     */
    public function draft(): self
    {
        return $this->with([
            'marking' => 'DRAFT',
        ]);
    }

    /**
     * LARP in WIP (Work In Progress) state
     */
    public function wip(): self
    {
        return $this->with([
            'marking' => 'WIP',
        ]);
    }

    /**
     * LARP in PUBLISHED state (visible to players)
     */
    public function published(): self
    {
        return $this->with([
            'marking' => 'PUBLISHED',
        ]);
    }

    /**
     * LARP in INQUIRIES state (accepting applications)
     */
    public function inquiries(): self
    {
        return $this->with([
            'marking' => 'INQUIRIES',
        ]);
    }

    /**
     * LARP in CONFIRMED state (ready to run)
     */
    public function confirmed(): self
    {
        return $this->with([
            'marking' => 'CONFIRMED',
        ]);
    }

    /**
     * LARP in CANCELLED state
     */
    public function cancelled(): self
    {
        return $this->with([
            'marking' => 'CANCELLED',
        ]);
    }

    /**
     * LARP in COMPLETED state (event finished)
     */
    public function completed(): self
    {
        return $this->with([
            'marking' => 'COMPLETED',
        ]);
    }

    /**
     * LARP with a specific location
     */
    public function withLocation(mixed $location = null): self
    {
        if ($location === null) {
            $location = LocationFactory::new()->approved()->create();
        }

        return $this->with([
            'location' => $location,
        ]);
    }

    /**
     * LARP with organizer participant
     */
    public function withOrganizer(mixed $user = null): self
    {
        $larp = $this->create();
        
        if ($user === null) {
            $user = \Tests\Support\Factory\Account\UserFactory::new()->organizer()->create();
        }

        LarpParticipantFactory::new()
            ->organizer()
            ->forLarp($larp)
            ->forUser($user)
            ->create();

        return $this;
    }
}
