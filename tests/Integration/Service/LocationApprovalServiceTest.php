<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use App\Domain\Core\Service\LocationApprovalService;
use App\Tests\Traits\AuthenticationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for LocationApprovalService
 *
 * Tests service layer business logic for location approval workflow
 */
class LocationApprovalServiceTest extends KernelTestCase
{
    use AuthenticationTestTrait;

    private ?LocationApprovalService $locationApprovalService = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->clearTestData();

        $this->locationApprovalService = static::getContainer()->get(LocationApprovalService::class);
    }

    protected function tearDown(): void
    {
        $this->clearTestData();
        parent::tearDown();
    }

    public function test_can_user_create_location_returns_false_for_pending_user(): void
    {
        $pendingUser = $this->createPendingUser();

        $canCreate = $this->locationApprovalService->canUserCreateLocation($pendingUser);

        $this->assertFalse(
            $canCreate,
            'PENDING user should not be able to create locations'
        );
    }

    public function test_can_user_create_location_returns_true_for_approved_user(): void
    {
        $approvedUser = $this->createApprovedUser();

        $canCreate = $this->locationApprovalService->canUserCreateLocation($approvedUser);

        $this->assertTrue(
            $canCreate,
            'APPROVED user should be able to create locations'
        );
    }

    public function test_can_user_create_location_returns_false_for_suspended_user(): void
    {
        $suspendedUser = $this->createSuspendedUser();

        $canCreate = $this->locationApprovalService->canUserCreateLocation($suspendedUser);

        $this->assertFalse(
            $canCreate,
            'SUSPENDED user should not be able to create locations'
        );
    }

    public function test_can_user_create_location_returns_false_for_banned_user(): void
    {
        $bannedUser = $this->createBannedUser();

        $canCreate = $this->locationApprovalService->canUserCreateLocation($bannedUser);

        $this->assertFalse(
            $canCreate,
            'BANNED user should not be able to create locations'
        );
    }

    public function test_can_user_create_location_returns_true_for_super_admin(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $canCreate = $this->locationApprovalService->canUserCreateLocation($superAdmin);

        $this->assertTrue(
            $canCreate,
            'SUPER_ADMIN should be able to create locations'
        );
    }

    public function test_approve_updates_location_status_correctly(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);

        $this->assertEquals(LocationApprovalStatus::PENDING, $location->getApprovalStatus());

        $this->locationApprovalService->approve($location, $superAdmin);

        $this->assertEquals(LocationApprovalStatus::APPROVED, $location->getApprovalStatus());
        $this->assertEquals($superAdmin, $location->getApprovedBy());
        $this->assertInstanceOf(\DateTimeInterface::class, $location->getApprovedAt());
        $this->assertNull($location->getRejectionReason());
    }

    public function test_approve_sets_approved_at_timestamp(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);

        $beforeApproval = new \DateTime();

        $this->locationApprovalService->approve($location, $superAdmin);

        $afterApproval = new \DateTime();

        $this->assertNotNull($location->getApprovedAt());
        $this->assertGreaterThanOrEqual(
            $beforeApproval->getTimestamp(),
            $location->getApprovedAt()->getTimestamp()
        );
        $this->assertLessThanOrEqual(
            $afterApproval->getTimestamp(),
            $location->getApprovedAt()->getTimestamp()
        );
    }

    public function test_reject_updates_location_status_correctly(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);

        $rejectionReason = 'Invalid address provided';

        $this->locationApprovalService->reject($location, $superAdmin, $rejectionReason);

        $this->assertEquals(LocationApprovalStatus::REJECTED, $location->getApprovalStatus());
        $this->assertEquals($rejectionReason, $location->getRejectionReason());
        $this->assertNull($location->getApprovedBy());
        $this->assertNull($location->getApprovedAt());
    }

    public function test_reject_with_null_reason(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);

        $this->locationApprovalService->reject($location, $superAdmin, null);

        $this->assertEquals(LocationApprovalStatus::REJECTED, $location->getApprovalStatus());
        $this->assertNull($location->getRejectionReason());
    }

    public function test_reject_clears_previous_approval(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);

        // First approve
        $this->locationApprovalService->approve($location, $superAdmin);

        $this->assertEquals(LocationApprovalStatus::APPROVED, $location->getApprovalStatus());
        $this->assertNotNull($location->getApprovedBy());
        $this->assertNotNull($location->getApprovedAt());

        // Then reject
        $this->locationApprovalService->reject($location, $superAdmin, 'Changed mind');

        $this->assertEquals(LocationApprovalStatus::REJECTED, $location->getApprovalStatus());
        $this->assertNull($location->getApprovedBy());
        $this->assertNull($location->getApprovedAt());
        $this->assertEquals('Changed mind', $location->getRejectionReason());
    }

    public function test_auto_approve_sets_correct_status_and_approver(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($superAdmin);

        $this->locationApprovalService->autoApprove($location, $superAdmin);

        $this->assertEquals(LocationApprovalStatus::APPROVED, $location->getApprovalStatus());
        $this->assertEquals($superAdmin, $location->getApprovedBy());
        $this->assertNotNull($location->getApprovedAt());
    }

    public function test_auto_approve_only_for_super_admin(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createLocation($superAdmin);

        $this->locationApprovalService->autoApprove($location, $superAdmin);

        $this->assertEquals(
            LocationApprovalStatus::APPROVED,
            $location->getApprovalStatus(),
            'SUPER_ADMIN location should be auto-approved'
        );
    }

    public function test_can_user_edit_location_returns_true_for_own_pending_location(): void
    {
        $user = $this->createApprovedUser();
        $location = $this->createPendingLocation($user);

        $canEdit = $this->locationApprovalService->canUserEditLocation($user, $location);

        $this->assertTrue(
            $canEdit,
            'User should be able to edit their own PENDING location'
        );
    }

    public function test_can_user_edit_location_returns_true_for_own_rejected_location(): void
    {
        $user = $this->createApprovedUser();
        $location = $this->createRejectedLocation($user);

        $canEdit = $this->locationApprovalService->canUserEditLocation($user, $location);

        $this->assertTrue(
            $canEdit,
            'User should be able to edit their own REJECTED location'
        );
    }

    public function test_can_user_edit_location_returns_false_for_own_approved_location(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);
        $this->locationApprovalService->approve($location, $superAdmin);

        $canEdit = $this->locationApprovalService->canUserEditLocation($user, $location);

        $this->assertFalse(
            $canEdit,
            'User should not be able to edit their own APPROVED location'
        );
    }

    public function test_can_user_edit_location_returns_false_for_other_users_location(): void
    {
        $user1 = $this->createApprovedUser('user1@example.com');
        $user2 = $this->createApprovedUser('user2@example.com');

        $location = $this->createPendingLocation($user1);

        $canEdit = $this->locationApprovalService->canUserEditLocation($user2, $location);

        $this->assertFalse(
            $canEdit,
            'User should not be able to edit other user\'s location'
        );
    }

    public function test_can_user_edit_location_returns_true_for_super_admin(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createApprovedLocation($user);

        $canEdit = $this->locationApprovalService->canUserEditLocation($superAdmin, $location);

        $this->assertTrue(
            $canEdit,
            'SUPER_ADMIN should be able to edit any location'
        );
    }

    public function test_approval_changes_persist_to_database(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);
        $locationId = $location->getId();

        $this->locationApprovalService->approve($location, $superAdmin);

        // Clear entity manager to force reload from database
        $this->getEntityManager()->clear();

        $reloadedLocation = $this->getEntityManager()->find(
            \App\Domain\Core\Entity\Location::class,
            $locationId
        );

        $this->assertNotNull($reloadedLocation);
        $this->assertEquals(
            LocationApprovalStatus::APPROVED,
            $reloadedLocation->getApprovalStatus(),
            'Approval should persist in database'
        );
        $this->assertNotNull($reloadedLocation->getApprovedBy());
        $this->assertNotNull($reloadedLocation->getApprovedAt());
    }

    public function test_rejection_changes_persist_to_database(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);
        $locationId = $location->getId();

        $this->locationApprovalService->reject($location, $superAdmin, 'Test reason');

        // Clear entity manager to force reload from database
        $this->getEntityManager()->clear();

        $reloadedLocation = $this->getEntityManager()->find(
            \App\Domain\Core\Entity\Location::class,
            $locationId
        );

        $this->assertNotNull($reloadedLocation);
        $this->assertEquals(
            LocationApprovalStatus::REJECTED,
            $reloadedLocation->getApprovalStatus(),
            'Rejection should persist in database'
        );
        $this->assertEquals('Test reason', $reloadedLocation->getRejectionReason());
    }
}
