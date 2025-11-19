<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Incidents;

use App\Domain\Incidents\Entity\Enum\LarpIncidentStatus;
use App\Domain\Incidents\Entity\LarpIncident;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<LarpIncident>
 */
final class LarpIncidentFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return LarpIncident::class;
    }

    protected function defaults(): array
    {
        return [
            'larp' => LarpFactory::new(),
            'createdBy' => UserFactory::new()->approved(),
            'reportCode' => strtoupper(self::faker()->lexify('??????')),
            'caseId' => strtoupper(self::faker()->bothify('INC-####-???')),
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-30 days', 'now')),
            'description' => self::faker()->paragraph(),
            'allowFeedback' => self::faker()->boolean(),
            'contactAccused' => self::faker()->boolean(),
            'allowMediator' => self::faker()->boolean(),
            'stayAnonymous' => self::faker()->boolean(),
            'status' => LarpIncidentStatus::NEW,
            'needsPoliceSupport' => self::faker()->optional()->boolean(),
        ];
    }



    // ========================================================================
    // Factory States (Incident Status)
    // ========================================================================

    /**
     * New incident (just reported)
     */
    public function new(): self
    {
        return $this->with([
            'status' => LarpIncidentStatus::NEW,
        ]);
    }

    /**
     * Incident in progress (being handled)
     */
    public function inProgress(): self
    {
        return $this->with([
            'status' => LarpIncidentStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Incident with feedback given
     */
    public function feedbackGiven(): self
    {
        return $this->with([
            'status' => LarpIncidentStatus::FEEDBACK_GIVEN,
        ]);
    }

    /**
     * Closed incident
     */
    public function closed(): self
    {
        return $this->with([
            'status' => LarpIncidentStatus::CLOSED,
        ]);
    }

    // ========================================================================
    // Factory Configuration Methods
    // ========================================================================

    /**
     * Incident for a specific LARP
     */
    public function forLarp(mixed $larp): self
    {
        return $this->with([
            'larp' => $larp,
        ]);
    }

    /**
     * Incident created by specific user
     */
    public function createdBy(mixed $user): self
    {
        return $this->with([
            'createdBy' => $user,
        ]);
    }

    /**
     * Anonymous incident report
     */
    public function anonymous(): self
    {
        return $this->with([
            'stayAnonymous' => true,
        ]);
    }

    /**
     * Incident that allows feedback
     */
    public function withFeedback(): self
    {
        return $this->with([
            'allowFeedback' => true,
        ]);
    }

    /**
     * Incident that requests mediator
     */
    public function withMediator(): self
    {
        return $this->with([
            'allowMediator' => true,
        ]);
    }

    /**
     * Incident that requests contacting accused
     */
    public function contactingAccused(): self
    {
        return $this->with([
            'contactAccused' => true,
        ]);
    }

    /**
     * Incident requiring police support
     */
    public function requiresPolice(): self
    {
        return $this->with([
            'needsPoliceSupport' => true,
        ]);
    }
}
