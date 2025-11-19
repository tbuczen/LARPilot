<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Application;

use App\Domain\Application\Entity\Enum\SubmissionStatus;
use App\Domain\Application\Entity\LarpApplication;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<LarpApplication>
 */
final class LarpApplicationFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return LarpApplication::class;
    }

    protected function defaults(): array
    {
        $approvedUser = UserFactory::new()->approved();
        return [
            'larp' => LarpFactory::new(),
            'user' => $approvedUser,
            'status' => SubmissionStatus::NEW,
            'notes' => self::faker()->optional()->paragraph(),
            'favouriteStyle' => self::faker()->optional()->paragraph(),
            'triggers' => self::faker()->optional()->paragraph(),
            'contactEmail' => self::faker()->optional()->safeEmail(),
            'createdBy' => $approvedUser,
        ];
    }

    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Larp $larp): void {})
            ;
    }

    /**
     * Application with CONSIDER status (under review)
     */
    public function consider(): self
    {
        return $this->with([
            'status' => SubmissionStatus::CONSIDER,
        ]);
    }

    /**
     * Application with REJECTED status
     */
    public function rejected(): self
    {
        return $this->with([
            'status' => SubmissionStatus::REJECTED,
        ]);
    }

    /**
     * Application with ACCEPTED status
     */
    public function accepted(): self
    {
        return $this->with([
            'status' => SubmissionStatus::ACCEPTED,
        ]);
    }

    /**
     * Application with OFFERED status (character assigned, awaiting confirmation)
     */
    public function offered(): self
    {
        return $this->with([
            'status' => SubmissionStatus::OFFERED,
        ]);
    }

    /**
     * Application with CONFIRMED status (player confirmed assignment)
     */
    public function confirmed(): self
    {
        return $this->with([
            'status' => SubmissionStatus::CONFIRMED,
        ]);
    }

    /**
     * Application with DECLINED status (player declined assignment)
     */
    public function declined(): self
    {
        return $this->with([
            'status' => SubmissionStatus::DECLINED,
        ]);
    }

    // ========================================================================
    // Factory Configuration Methods
    // ========================================================================

    /**
     * Application for a specific LARP
     */
    public function forLarp(mixed $larp): self
    {
        return $this->with([
            'larp' => $larp,
        ]);
    }

    /**
     * Application for a specific User
     */
    public function forUser(mixed $user): self
    {
        return $this->with([
            'user' => $user,
            'createdBy' => $user,
        ]);
    }

    /**
     * Application with specific notes
     */
    public function withNotes(string $notes): self
    {
        return $this->with([
            'notes' => $notes,
        ]);
    }

    /**
     * Application with specific contact email
     */
    public function withContactEmail(string $contactEmail): self
    {
        return $this->with([
            'contactEmail' => $contactEmail,
        ]);
    }

    /**
     * Application with choices
     */
    public function withChoices(int $count = 3): self
    {
        return $this->afterPersist(function (LarpApplication $application) use ($count) {
            LarpApplicationChoiceFactory::new()
                ->forApplication($application)
                ->many($count)
                ->create();
        });
    }
}
