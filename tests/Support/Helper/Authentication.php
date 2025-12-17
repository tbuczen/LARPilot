<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use App\Domain\Account\Entity\Plan;
use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Entity\Location;
use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\Module\Symfony;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tests\Support\Factory\Account\PlanFactory;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\Core\LocationFactory;

/**
 * Authentication Helper for Codeception Tests
 *
 * Integrates Foundry factories with Codeception actors:
 * - Session-based authentication via amLoggedInAs()
 * - User creation using UserFactory
 * - LARP/Location creation using domain factories
 * - Route generation and service access through Symfony module
 */
class Authentication extends Module
{
    protected ?Symfony $symfony = null;

    /**
     * @throws ModuleException
     */
    public function _beforeSuite($settings = []): void
    {
        /** @var Symfony $module */
        $module = $this->getModule('Symfony');
        $this->symfony = $module;
    }

    /**
     * Clean up after each test to prevent connection pool exhaustion
     */
    public function _after(\Codeception\TestInterface $test): void
    {
        // Close the entity manager's connection to free up the database connection
        try {
            if ($this->symfony && $this->symfony->client) {
                $container = $this->symfony->client->getContainer();
                if ($container && $container->has('doctrine.orm.entity_manager')) {
                    $em = $container->get('doctrine.orm.entity_manager');
                    if ($em && $em->getConnection()->isConnected()) {
                        $em->getConnection()->close();
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently ignore cleanup errors
        }
    }

    /**
     * Log in as a specific user by creating a session token
     * This is the core authentication functionality for Codeception
     */
    public function amLoggedInAs(User $user, string $firewall = 'main'): void
    {
        $client = $this->symfony->client;
        $session = $client->getContainer()->get('session.factory')->createSession();
        $token = new UsernamePasswordToken($user, $firewall, $user->getRoles());
        $session->set('_security_' . $firewall, serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }

    /**
     * Get EntityManager instance
     */
    public function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $service */
        $service = $this->symfony->grabService(EntityManagerInterface::class);
        return $service;
    }

    /**
     * Generate URL from route name (convenience method)
     */
    public function getUrl(string $route, array $parameters = []): string
    {
        return $this->symfony->grabService('router')->generate($route, $parameters);
    }

    public function createSuperAdmin(): User
    {
        return UserFactory::new()->approved()->superAdmin()->create()->_real();
    }

    /**
     * Create a pending location (awaiting approval)
     */
    public function createPendingLocation(User $creator, string $name = 'Pending Location'): Location
    {
        return LocationFactory::new()
            ->pending()
            ->createdBy($creator)
            ->with(['title' => $name])
            ->create()
            ->_real();
    }

    /**
     * Create an approved location
     */
    public function createApprovedLocation(User $creator, string $name = 'Approved Location'): Location
    {
        return LocationFactory::new()
            ->approved()
            ->createdBy($creator)
            ->approvedBy($creator)
            ->with(['title' => $name])
            ->create()
            ->_real();
    }

    /**
     * Create a rejected location
     */
    public function createRejectedLocation(
        User $creator,
        string $reason = 'Does not meet requirements',
        string $name = 'Rejected Location'
    ): Location {
        return LocationFactory::new()
            ->rejected($reason)
            ->createdBy($creator)
            ->with(['title' => $name])
            ->create()
            ->_real();
    }

    // ========================================================================
    // User Factory Methods
    // ========================================================================

    /**
     * Create a PENDING user
     */
    public function createPendingUser(): User
    {
        return UserFactory::createPendingUser();
    }

    /**
     * Create an APPROVED user with optional plan
     */
    public function createApprovedUser(?string $name = null, ?Plan $plan = null): User
    {
        $factory = UserFactory::new()->approved();

        if ($name !== null) {
            $factory = $factory->with(['username' => $name]);
        }

        if ($plan !== null) {
            $factory = $factory->with(['plan' => $plan]);
        }

        return $factory->create()->_real();
    }

    /**
     * Create a SUSPENDED user
     */
    public function createSuspendedUser(): User
    {
        return UserFactory::createSuspendedUser();
    }

    /**
     * Create a BANNED user
     */
    public function createBannedUser(): User
    {
        return UserFactory::createBannedUser();
    }

    // ========================================================================
    // Plan Factory Methods
    // ========================================================================

    /**
     * Create a free plan (maxLarps = 1)
     */
    public function createFreePlan(): Plan
    {
        return PlanFactory::new()->free()->create()->_real();
    }

    /**
     * Create a premium plan with specified maxLarps
     */
    public function createPremiumPlan(int $maxLarps = 10): Plan
    {
        return PlanFactory::new()
            ->premium()
            ->with(['maxLarps' => $maxLarps])
            ->create()
            ->_real();
    }

    /**
     * Create an unlimited plan (no LARP limit)
     */
    public function createUnlimitedPlan(): Plan
    {
        return PlanFactory::new()->unlimited()->create()->_real();
    }

    // ========================================================================
    // LARP Factory Methods
    // ========================================================================

    /**
     * Create a LARP with specified status (default: DRAFT)
     */
    public function createLarp(User $organizer, ?LarpStageStatus $status = null): Larp
    {
        $status = $status ?? LarpStageStatus::DRAFT;

        $larpFactory = LarpFactory::new()
            ->withStatus($status)
            ->withCreator($organizer);

        $larp = $larpFactory->create();

        // Add organizer as participant
        LarpParticipantFactory::new()
            ->forUser($organizer)
            ->organizer()
            ->forLarp($larp)
            ->create();

        return $larp->_real();
    }

    /**
     * Create a DRAFT LARP
     */
    public function createDraftLarp(User $organizer, ?string $title = null): Larp
    {
        return LarpFactory::createDraftLarp($organizer, $title)->_real();
    }

    /**
     * Create a PUBLISHED LARP
     */
    public function createPublishedLarp(User $organizer, ?string $title = null): Larp
    {
        return LarpFactory::createPublishedLarp($organizer, $title)->_real();
    }

    /**
     * Create a WIP (Work In Progress) LARP
     */
    public function createWipLarp(User $organizer): Larp
    {
        $larpFactory = LarpFactory::new()
            ->wip()
            ->withCreator($organizer);

        $larp = $larpFactory->create();

        LarpParticipantFactory::new()
            ->forUser($organizer)
            ->organizer()
            ->forLarp($larp)
            ->create();

        return $larp->_real();
    }

    // ========================================================================
    // Location Factory Methods
    // ========================================================================

    /**
     * Create a Location with specified status (default: PENDING)
     */
    public function createLocation(
        User $creator,
        ?LocationApprovalStatus $status = null,
        ?string $name = null
    ): Location {
        $status = $status ?? LocationApprovalStatus::PENDING;

        $factory = LocationFactory::new()
            ->createdBy($creator);

        if ($name !== null) {
            $factory = $factory->with(['title' => $name]);
        }

        $factory = match ($status) {
            LocationApprovalStatus::PENDING => $factory->pending(),
            LocationApprovalStatus::APPROVED => $factory->approved()->approvedBy($creator),
            LocationApprovalStatus::REJECTED => $factory->rejected(),
        };

        return $factory->create()->_real();
    }

    // ========================================================================
    // Participant Factory Methods
    // ========================================================================

    /**
     * Add a participant to a LARP with specified roles (default: PLAYER)
     *
     * @param ParticipantRole[] $roles
     */
    public function addParticipantToLarp(
        Larp $larp,
        User $user,
        array $roles = []
    ): LarpParticipant {
        if (empty($roles)) {
            $roles = [ParticipantRole::PLAYER];
        }

        return LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($user)
            ->withRoles($roles)
            ->create()
            ->_real();
    }

    // ========================================================================
    // HTTP Request Methods
    // ========================================================================

    /**
     * Send a POST request to a URL
     *
     * @param string $url The URL to send the POST request to
     * @param array $params The POST parameters
     */
    public function sendPOST(string $url, array $params = []): void
    {
        $this->symfony->client->request('POST', $url, $params);
    }

    /**
     * Send a GET request to a URL
     *
     * @param string $url The URL to send the GET request to
     * @param array $params The query parameters
     */
    public function sendGET(string $url, array $params = []): void
    {
        $this->symfony->client->request('GET', $url, $params);
    }

    /**
     * Grab the Response object from the last request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function grabResponse(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->symfony->client->getResponse();
    }

    /**
     * Check if response code is between min and max (inclusive)
     *
     * @param int $min Minimum expected status code
     * @param int $max Maximum expected status code
     */
    public function seeResponseCodeIsBetween(int $min, int $max): void
    {
        $statusCode = $this->symfony->client->getResponse()->getStatusCode();
        \PHPUnit\Framework\Assert::assertGreaterThanOrEqual(
            $min,
            $statusCode,
            "Expected status code to be at least {$min}, got {$statusCode}"
        );
        \PHPUnit\Framework\Assert::assertLessThanOrEqual(
            $max,
            $statusCode,
            "Expected status code to be at most {$max}, got {$statusCode}"
        );
    }

    /**
     * Grab the HTTP response code from the last request
     *
     * @return int The HTTP status code
     */
    public function grabHttpResponseCode(): int
    {
        return $this->symfony->client->getResponse()->getStatusCode();
    }
}
