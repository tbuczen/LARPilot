<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Domain\Account\Entity\Enum\UserStatus;
use App\Domain\Account\Entity\Plan;
use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait AuthenticationTestTrait
{
    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * Generate URL from route name and parameters
     */
    private function generateUrl(string $route, array $parameters = []): string
    {
        $router = static::getContainer()->get(UrlGeneratorInterface::class);
        return $router->generate($route, $parameters);
    }

    /**
     * Create a test user with specified status
     */
    private function createUser(
        string $username,
        UserStatus $status = UserStatus::PENDING,
        array $roles = [],
        ?Plan $plan = null
    ): User {
        $user = new User();
        $user->setUsername($username);
        $user->setContactEmail($username . '@example.com');
        $user->setRoles($roles);
        $user->setStatus($status);

        if ($plan !== null) {
            $user->setPlan($plan);
        }

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * Create a PENDING user (default new user state)
     */
    private function createPendingUser(?string $username = null): User
    {
        $username = $username ?? 'pending_user_' . uniqid();
        return $this->createUser($username, UserStatus::PENDING);
    }

    /**
     * Create an APPROVED user
     */
    private function createApprovedUser(?string $username = null, ?Plan $plan = null): User
    {
        $username = $username ?? 'approved_user_' . uniqid();
        return $this->createUser($username, UserStatus::APPROVED, [], $plan);
    }

    /**
     * Create a SUSPENDED user
     */
    private function createSuspendedUser(?string $username = null): User
    {
        $username = $username ?? 'suspended_user_' . uniqid();
        return $this->createUser($username, UserStatus::SUSPENDED);
    }

    /**
     * Create a BANNED user
     */
    private function createBannedUser(?string $username = null): User
    {
        $username = $username ?? 'banned_user_' . uniqid();
        return $this->createUser($username, UserStatus::BANNED);
    }

    /**
     * Create a SUPER_ADMIN user
     */
    private function createSuperAdmin(?string $username = null): User
    {
        $username = $username ?? 'super_admin_' . uniqid();
        return $this->createUser($username, UserStatus::APPROVED, ['ROLE_SUPER_ADMIN']);
    }

    /**
     * Create a Plan with specified limits
     */
    private function createPlan(
        string $name,
        ?int $maxLarps = null,
        bool $isActive = true
    ): Plan {
        $plan = new Plan();
        $plan->setName($name);
        $plan->setMaxLarps($maxLarps);
        $plan->setIsActive($isActive);
        $plan->setDescription("Test plan: {$name}");

        $em = $this->getEntityManager();
        $em->persist($plan);
        $em->flush();

        return $plan;
    }

    /**
     * Create a free tier plan (1 LARP limit)
     */
    private function createFreePlan(): Plan
    {
        return $this->createPlan('Free Tier ' . uniqid(), 1);
    }

    /**
     * Create an unlimited plan
     */
    private function createUnlimitedPlan(): Plan
    {
        return $this->createPlan('Unlimited ' . uniqid(), null);
    }

    /**
     * Create a premium plan with custom limit
     */
    private function createPremiumPlan(int $maxLarps = 5): Plan
    {
        return $this->createPlan("Premium ({$maxLarps} LARPs) " . uniqid(), $maxLarps);
    }

    /**
     * Create a LARP with specified status and organizer
     */
    private function createLarp(
        User $organizer,
        LarpStageStatus $status = LarpStageStatus::DRAFT,
        string $title = 'Test LARP'
    ): Larp {
        $larp = new Larp();
        $larp->setTitle($title);
        $larp->setDescription('A test LARP for automated testing');
        $larp->setStatus($status);
        $larp->setMarking($status->value);
        $larp->setCreatedBy($organizer); // Set the creator (required field)

        $startDate = new \DateTime('+1 month');
        $endDate = (clone $startDate)->modify('+3 days');
        $larp->setStartDate($startDate);
        $larp->setEndDate($endDate);

        $em = $this->getEntityManager();
        $em->persist($larp);

        // Add organizer as participant
        $this->addParticipantToLarp($larp, $organizer, [ParticipantRole::ORGANIZER]);

        $em->flush();

        // Ensure slug is generated (Gedmo might not trigger in tests)
        if ($larp->getSlug() === null) {
            // Generate slug manually if Gedmo didn't generate it
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
            $larp->setSlug($slug);
            $em->flush();
        }

        // Refresh to get the latest state from database
        $em->refresh($larp);

        return $larp;
    }

    /**
     * Create a DRAFT LARP
     */
    private function createDraftLarp(User $organizer, string $title = 'Draft LARP'): Larp
    {
        return $this->createLarp($organizer, LarpStageStatus::DRAFT, $title);
    }

    /**
     * Create a PUBLISHED LARP (publicly visible)
     */
    private function createPublishedLarp(User $organizer, string $title = 'Published LARP'): Larp
    {
        return $this->createLarp($organizer, LarpStageStatus::PUBLISHED, $title);
    }

    /**
     * Create a WIP LARP
     */
    private function createWipLarp(User $organizer, string $title = 'WIP LARP'): Larp
    {
        return $this->createLarp($organizer, LarpStageStatus::WIP, $title);
    }

    /**
     * Add a participant to a LARP with specific roles
     */
    private function addParticipantToLarp(
        Larp $larp,
        User $user,
        array $roles = [ParticipantRole::PLAYER]
    ): LarpParticipant {
        $participant = new LarpParticipant();
        $participant->setUser($user);
        $participant->setRoles(array_map(fn ($role) => $role->value, $roles));

        // Use addParticipant() to properly manage both sides of the relationship
        $larp->addParticipant($participant);

        $em = $this->getEntityManager();
        $em->persist($participant);
        $em->flush();

        return $participant;
    }

    /**
     * Create a Location with specified approval status
     */
    private function createLocation(
        User $creator,
        LocationApprovalStatus $approvalStatus = LocationApprovalStatus::PENDING,
        string $name = 'Test Location'
    ): Location {
        $location = new Location();
        $location->setTitle($name);
        $location->setAddress('123 Test Street');
        $location->setCity('Test City');
        $location->setCountry('Test Country');
        $location->setPostalCode('12345');
        $location->setLatitude('52.2297');
        $location->setLongitude('21.0122');
        $location->setCreatedBy($creator);
        $location->setApprovalStatus($approvalStatus);

        $em = $this->getEntityManager();
        $em->persist($location);
        $em->flush();

        return $location;
    }

    /**
     * Create a PENDING Location
     */
    private function createPendingLocation(User $creator, string $name = 'Pending Location'): Location
    {
        return $this->createLocation($creator, LocationApprovalStatus::PENDING, $name);
    }

    /**
     * Create an APPROVED Location
     */
    private function createApprovedLocation(User $creator, string $name = 'Approved Location'): Location
    {
        $location = $this->createLocation($creator, LocationApprovalStatus::APPROVED, $name);
        $location->setApprovedBy($creator);
        $location->setApprovedAt(new \DateTime());

        $em = $this->getEntityManager();
        $em->flush();

        return $location;
    }

    /**
     * Create a REJECTED Location
     */
    private function createRejectedLocation(
        User $creator,
        string $name = 'Rejected Location',
        string $reason = 'Test rejection reason'
    ): Location {
        $location = $this->createLocation($creator, LocationApprovalStatus::REJECTED, $name);
        $location->setRejectionReason($reason);

        $em = $this->getEntityManager();
        $em->flush();

        return $location;
    }

    /**
     * Approve a user programmatically
     */
    private function approveUser(User $user): void
    {
        $user->setStatus(UserStatus::APPROVED);

        $em = $this->getEntityManager();
        $em->flush();
    }

    /**
     * Suspend a user programmatically
     */
    private function suspendUser(User $user): void
    {
        $user->setStatus(UserStatus::SUSPENDED);

        $em = $this->getEntityManager();
        $em->flush();
    }

    /**
     * Ban a user programmatically
     */
    private function banUser(User $user): void
    {
        $user->setStatus(UserStatus::BANNED);

        $em = $this->getEntityManager();
        $em->flush();
    }

    /**
     * Clear all test data
     */
    private function clearTestData(): void
    {
        $em = $this->getEntityManager();

        try {
            // Disable foreign key checks temporarily for cleanup
            $connection = $em->getConnection();

            // Use TRUNCATE with CASCADE to handle all foreign keys
            $connection->executeStatement('SET CONSTRAINTS ALL DEFERRED');

            // Clear in correct order to respect foreign key constraints
            // First delete child tables that reference others
            $em->createQuery('DELETE FROM App\Domain\Core\Entity\LarpParticipant')->execute();

            // Delete Story Objects (they reference LARPs)
            $connection->executeStatement('TRUNCATE TABLE story_object RESTART IDENTITY CASCADE');

            // Delete Locations
            $em->createQuery('DELETE FROM App\Domain\Core\Entity\Location')->execute();

            // Delete LARPs
            $em->createQuery('DELETE FROM App\Domain\Core\Entity\Larp')->execute();

            // Delete Users
            $em->createQuery('DELETE FROM App\Domain\Account\Entity\User')->execute();

            // Delete Plans
            $em->createQuery('DELETE FROM App\Domain\Core\Entity\Plan')->execute();

            $connection->executeStatement('SET CONSTRAINTS ALL IMMEDIATE');

            $em->clear();
        } catch (\Exception $e) {
            // If cleanup fails, just clear the entity manager
            $em->clear();
        }
    }
}
