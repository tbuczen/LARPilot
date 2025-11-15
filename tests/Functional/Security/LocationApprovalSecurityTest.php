<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use App\Tests\Traits\AuthenticationTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests Location approval workflow and permissions
 *
 * Covers:
 * - Users can edit their PENDING/REJECTED locations
 * - Users cannot edit APPROVED locations
 * - Only SUPER_ADMIN can approve locations
 * - Only SUPER_ADMIN can reject locations
 * - Users can delete their PENDING/REJECTED locations
 * - Users cannot delete APPROVED locations
 * - SUPER_ADMIN can edit/delete any location
 * - Rejection reason is stored properly
 */
class LocationApprovalSecurityTest extends WebTestCase
{
    use AuthenticationTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
    }


    public function test_user_can_edit_their_pending_location(): void
    {
        $user = $this->createApprovedUser();
        $location = $this->createPendingLocation($user);

        $this->client->loginUser($user);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canEdit = $authChecker->isGranted('EDIT_LOCATION', $location);

        $this->assertTrue($canEdit, 'User should be able to edit their PENDING location');
    }

    public function test_user_can_edit_their_rejected_location(): void
    {
        $user = $this->createApprovedUser();
        $location = $this->createRejectedLocation($user);

        $this->client->loginUser($user);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canEdit = $authChecker->isGranted('EDIT_LOCATION', $location);

        $this->assertTrue($canEdit, 'User should be able to edit their REJECTED location');
    }

    public function test_user_cannot_edit_their_approved_location(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);

        // Approve it
        $locationApprovalService = static::getContainer()->get(
            \App\Domain\Core\Service\LocationApprovalService::class
        );
        $locationApprovalService->approve($location, $superAdmin);

        $this->client->loginUser($user);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canEdit = $authChecker->isGranted('EDIT_LOCATION', $location);

        $this->assertFalse($canEdit, 'User should not be able to edit their APPROVED location');
    }

    public function test_user_cannot_edit_other_users_location(): void
    {
        $user1 = $this->createApprovedUser();
        $user2 = $this->createApprovedUser();

        $location = $this->createPendingLocation($user1);

        $this->client->loginUser($user2);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canEdit = $authChecker->isGranted('EDIT_LOCATION', $location);

        $this->assertFalse($canEdit, 'User should not be able to edit other user\'s location');
    }

    public function test_regular_user_cannot_approve_location(): void
    {
        $user = $this->createApprovedUser();
        $location = $this->createPendingLocation($user);

        $this->client->loginUser($user);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canApprove = $authChecker->isGranted('APPROVE_LOCATION', $location);

        $this->assertFalse($canApprove, 'Regular user should not be able to approve locations');
    }

    public function test_super_admin_can_approve_location(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);

        $this->client->loginUser($superAdmin);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canApprove = $authChecker->isGranted('APPROVE_LOCATION', $location);

        $this->assertTrue($canApprove, 'SUPER_ADMIN should be able to approve locations');
    }

    public function test_location_approval_updates_status(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);

        $this->assertEquals(LocationApprovalStatus::PENDING, $location->getApprovalStatus());

        $locationApprovalService = static::getContainer()->get(
            \App\Domain\Core\Service\LocationApprovalService::class
        );
        $locationApprovalService->approve($location, $superAdmin);

        $this->assertEquals(LocationApprovalStatus::APPROVED, $location->getApprovalStatus());
        $this->assertEquals($superAdmin, $location->getApprovedBy());
        $this->assertNotNull($location->getApprovedAt());
    }

    public function test_regular_user_cannot_reject_location(): void
    {
        $user = $this->createApprovedUser();
        $location = $this->createPendingLocation($user);

        $this->client->loginUser($user);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canReject = $authChecker->isGranted('REJECT_LOCATION', $location);

        $this->assertFalse($canReject, 'Regular user should not be able to reject locations');
    }

    public function test_super_admin_can_reject_location(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);

        $this->client->loginUser($superAdmin);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canReject = $authChecker->isGranted('REJECT_LOCATION', $location);

        $this->assertTrue($canReject, 'SUPER_ADMIN should be able to reject locations');
    }

    public function test_location_rejection_stores_reason(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);

        $rejectionReason = 'Invalid address provided';

        $locationApprovalService = static::getContainer()->get(
            \App\Domain\Core\Service\LocationApprovalService::class
        );
        $locationApprovalService->reject($location, $superAdmin, $rejectionReason);

        $this->assertEquals(LocationApprovalStatus::REJECTED, $location->getApprovalStatus());
        $this->assertEquals($rejectionReason, $location->getRejectionReason());
    }

    public function test_user_can_delete_their_pending_location(): void
    {
        $user = $this->createApprovedUser();
        $location = $this->createPendingLocation($user);

        $this->client->loginUser($user);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canDelete = $authChecker->isGranted('DELETE_LOCATION', $location);

        $this->assertTrue($canDelete, 'User should be able to delete their PENDING location');
    }

    public function test_user_can_delete_their_rejected_location(): void
    {
        $user = $this->createApprovedUser();
        $location = $this->createRejectedLocation($user);

        $this->client->loginUser($user);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canDelete = $authChecker->isGranted('DELETE_LOCATION', $location);

        $this->assertTrue($canDelete, 'User should be able to delete their REJECTED location');
    }

    public function test_user_cannot_delete_their_approved_location(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);

        // Approve it
        $locationApprovalService = static::getContainer()->get(
            \App\Domain\Core\Service\LocationApprovalService::class
        );
        $locationApprovalService->approve($location, $superAdmin);

        $this->client->loginUser($user);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canDelete = $authChecker->isGranted('DELETE_LOCATION', $location);

        $this->assertFalse($canDelete, 'User should not be able to delete their APPROVED location');
    }

    public function test_super_admin_can_delete_any_location(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createApprovedLocation($user);

        $this->client->loginUser($superAdmin);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canDelete = $authChecker->isGranted('DELETE_LOCATION', $location);

        $this->assertTrue($canDelete, 'SUPER_ADMIN should be able to delete any location');
    }

    public function test_super_admin_can_edit_any_location(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createApprovedLocation($user);

        $this->client->loginUser($superAdmin);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canEdit = $authChecker->isGranted('EDIT_LOCATION', $location);

        $this->assertTrue($canEdit, 'SUPER_ADMIN should be able to edit any location');
    }

    public function test_super_admin_can_access_approve_location_route(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);

        $this->client->loginUser($superAdmin);

        $this->client->request('POST', $this->generateUrl("backoffice_location_approve", ["id" => $location->getId()]));

        // Should be successful or redirect (not 403)
        $this->assertNotEquals(
            403,
            $this->client->getResponse()->getStatusCode(),
            'SUPER_ADMIN should be able to access approve route'
        );
    }

    public function test_regular_user_cannot_access_approve_location_route(): void
    {
        $user = $this->createApprovedUser();

        $location = $this->createPendingLocation($user);

        $this->client->loginUser($user);

        $this->client->request('POST', $this->generateUrl("backoffice_location_approve", ["id" => $location->getId()]));

        $this->assertResponseStatusCodeSame(
            403,
            'Regular user should not be able to access approve route'
        );
    }

    public function test_super_admin_can_access_reject_location_route(): void
    {
        $user = $this->createApprovedUser();
        $superAdmin = $this->createSuperAdmin();

        $location = $this->createPendingLocation($user);

        $this->client->loginUser($superAdmin);

        $this->client->request('POST', $this->generateUrl("backoffice_location_reject", ["id" => $location->getId()]), [
            'reason' => 'Test rejection',
        ]);

        // Should be successful or redirect (not 403)
        $this->assertNotEquals(
            403,
            $this->client->getResponse()->getStatusCode(),
            'SUPER_ADMIN should be able to access reject route'
        );
    }

    public function test_regular_user_cannot_access_reject_location_route(): void
    {
        $user = $this->createApprovedUser();

        $location = $this->createPendingLocation($user);

        $this->client->loginUser($user);

        $this->client->request('POST', $this->generateUrl("backoffice_location_reject", ["id" => $location->getId()]), [
            'reason' => 'Test rejection',
        ]);

        $this->assertResponseStatusCodeSame(
            403,
            'Regular user should not be able to access reject route'
        );
    }

    public function test_pending_user_cannot_create_location(): void
    {
        $pendingUser = $this->createPendingUser();

        $this->client->loginUser($pendingUser);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canCreate = $authChecker->isGranted('CREATE_LOCATION');

        $this->assertFalse($canCreate, 'PENDING user should not be able to create locations');
    }

    public function test_approved_user_can_create_location(): void
    {
        $approvedUser = $this->createApprovedUser();

        $this->client->loginUser($approvedUser);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canCreate = $authChecker->isGranted('CREATE_LOCATION');

        $this->assertTrue($canCreate, 'APPROVED user should be able to create locations');
    }
}
