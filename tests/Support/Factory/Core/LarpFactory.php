<?php

namespace Tests\Support\Factory\Core;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Larp;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;

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

    public function withStatus(LarpStageStatus $status): self
    {
        return $this->with([
            'marking' => $status->value,
        ]);
    }

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

    public function withCreator(mixed $user): self
    {
        return $this->with([
            'createdBy' => $user,
        ]);
    }

    public static function createDraftLarp(User|Proxy $user, ?string $title = null): Larp|Proxy
    {
        $larpFactory = LarpFactory::new()
            ->draft()
            ->withCreator($user);

        if ($title !== null) {
            $larpFactory = $larpFactory->withTitle($title);
        }

        // Create the LARP first so it has an ID
        $larp = $larpFactory->create();

        // Now create the participant with the persisted LARP
        LarpParticipantFactory::new()
            ->forUser($user)
            ->organizer()
            ->forLarp($larp)
            ->create();

        return $larp;
    }

    public static function createPublishedLarp(User|Proxy $user, ?string $title = null): Larp|Proxy
    {
        $larpFactory = LarpFactory::new()
            ->published()
            ->withCreator($user);

        if ($title !== null) {
            $larpFactory = $larpFactory->withTitle($title);
        }

        // Create the LARP first so it has an ID
        $larp = $larpFactory->create();

        // Now create the participant with the persisted LARP
        LarpParticipantFactory::new()
            ->forUser($user)
            ->organizer()
            ->forLarp($larp)
            ->create();

        return $larp;
    }

    public function withTitle(string $title): self
    {
        return $this->with([
            'title' => $title,
        ]);
    }
}
