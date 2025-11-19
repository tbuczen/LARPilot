<?php

declare(strict_types=1);

namespace Tests\Functional\Security;

use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use Tests\Support\FunctionalTester;

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
class LocationApprovalSecurityCest
{
    public function userCanEditTheirPendingLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that users can edit their PENDING locations');

        $user = UserFactory::createApprovedUser();
        $location = $I->createPendingLocation($user);

        $I->amLoggedInAs($user);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canEdit = $authChecker->isGranted('EDIT_LOCATION', $location);

        $I->assertTrue($canEdit, 'User should be able to edit their PENDING location');
    }

    public function userCanEditTheirRejectedLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that users can edit their REJECTED locations');

        $user = UserFactory::createApprovedUser();
        $location = $I->createRejectedLocation($user);

        $I->amLoggedInAs($user);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canEdit = $authChecker->isGranted('EDIT_LOCATION', $location);

        $I->assertTrue($canEdit, 'User should be able to edit their REJECTED location');
    }

    public function userCannotEditTheirApprovedLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that users cannot edit their APPROVED locations');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);

        // Approve it
        $locationApprovalService = $I->getContainer()->get(
            \App\Domain\Core\Service\LocationApprovalService::class
        );
        $locationApprovalService->approve($location, $superAdmin);

        $I->amLoggedInAs($user);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canEdit = $authChecker->isGranted('EDIT_LOCATION', $location);

        $I->assertFalse($canEdit, 'User should not be able to edit their APPROVED location');
    }

    public function userCannotEditOtherUsersLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that users cannot edit other users locations');

        $user1 = UserFactory::createApprovedUser();
        $user2 = UserFactory::createApprovedUser();

        $location = $I->createPendingLocation($user1);

        $I->amLoggedInAs($user2);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canEdit = $authChecker->isGranted('EDIT_LOCATION', $location);

        $I->assertFalse($canEdit, 'User should not be able to edit other user\'s location');
    }

    public function regularUserCannotApproveLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that regular users cannot approve locations');

        $user = UserFactory::createApprovedUser();
        $location = $I->createPendingLocation($user);

        $I->amLoggedInAs($user);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canApprove = $authChecker->isGranted('APPROVE_LOCATION', $location);

        $I->assertFalse($canApprove, 'Regular user should not be able to approve locations');
    }

    public function superAdminCanApproveLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that super admins can approve locations');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);

        $I->amLoggedInAs($superAdmin);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canApprove = $authChecker->isGranted('APPROVE_LOCATION', $location);

        $I->assertTrue($canApprove, 'SUPER_ADMIN should be able to approve locations');
    }

    public function locationApprovalUpdatesStatus(FunctionalTester $I): void
    {
        $I->wantTo('verify that location approval updates the status');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);

        $I->assertEquals(LocationApprovalStatus::PENDING, $location->getApprovalStatus());

        $locationApprovalService = $I->getContainer()->get(
            \App\Domain\Core\Service\LocationApprovalService::class
        );
        $locationApprovalService->approve($location, $superAdmin);

        $I->assertEquals(LocationApprovalStatus::APPROVED, $location->getApprovalStatus());
        $I->assertEquals($superAdmin, $location->getApprovedBy());
        $I->assertNotNull($location->getApprovedAt());
    }

    public function regularUserCannotRejectLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that regular users cannot reject locations');

        $user = UserFactory::createApprovedUser();
        $location = $I->createPendingLocation($user);

        $I->amLoggedInAs($user);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canReject = $authChecker->isGranted('REJECT_LOCATION', $location);

        $I->assertFalse($canReject, 'Regular user should not be able to reject locations');
    }

    public function superAdminCanRejectLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that super admins can reject locations');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);

        $I->amLoggedInAs($superAdmin);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canReject = $authChecker->isGranted('REJECT_LOCATION', $location);

        $I->assertTrue($canReject, 'SUPER_ADMIN should be able to reject locations');
    }

    public function locationRejectionStoresReason(FunctionalTester $I): void
    {
        $I->wantTo('verify that location rejection stores the reason');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);

        $rejectionReason = 'Invalid address provided';

        $locationApprovalService = $I->getContainer()->get(
            \App\Domain\Core\Service\LocationApprovalService::class
        );
        $locationApprovalService->reject($location, $superAdmin, $rejectionReason);

        $I->assertEquals(LocationApprovalStatus::REJECTED, $location->getApprovalStatus());
        $I->assertEquals($rejectionReason, $location->getRejectionReason());
    }

    public function userCanDeleteTheirPendingLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that users can delete their PENDING locations');

        $user = UserFactory::createApprovedUser();
        $location = $I->createPendingLocation($user);

        $I->amLoggedInAs($user);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canDelete = $authChecker->isGranted('DELETE_LOCATION', $location);

        $I->assertTrue($canDelete, 'User should be able to delete their PENDING location');
    }

    public function userCanDeleteTheirRejectedLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that users can delete their REJECTED locations');

        $user = UserFactory::createApprovedUser();
        $location = $I->createRejectedLocation($user);

        $I->amLoggedInAs($user);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canDelete = $authChecker->isGranted('DELETE_LOCATION', $location);

        $I->assertTrue($canDelete, 'User should be able to delete their REJECTED location');
    }

    public function userCannotDeleteTheirApprovedLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that users cannot delete their APPROVED locations');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);

        // Approve it
        $locationApprovalService = $I->getContainer()->get(
            \App\Domain\Core\Service\LocationApprovalService::class
        );
        $locationApprovalService->approve($location, $superAdmin);

        $I->amLoggedInAs($user);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canDelete = $authChecker->isGranted('DELETE_LOCATION', $location);

        $I->assertFalse($canDelete, 'User should not be able to delete their APPROVED location');
    }

    public function superAdminCanDeleteAnyLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that super admins can delete any location');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createApprovedLocation($user);

        $I->amLoggedInAs($superAdmin);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canDelete = $authChecker->isGranted('DELETE_LOCATION', $location);

        $I->assertTrue($canDelete, 'SUPER_ADMIN should be able to delete any location');
    }

    public function superAdminCanEditAnyLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that super admins can edit any location');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createApprovedLocation($user);

        $I->amLoggedInAs($superAdmin);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canEdit = $authChecker->isGranted('EDIT_LOCATION', $location);

        $I->assertTrue($canEdit, 'SUPER_ADMIN should be able to edit any location');
    }

    public function superAdminCanAccessApproveLocationRoute(FunctionalTester $I): void
    {
        $I->wantTo('verify that super admins can access the location approve route');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);

        $I->amLoggedInAs($superAdmin);

        $I->sendPOST($I->getUrl('backoffice_location_approve', ['id' => $location->getId()]));

        // Should be successful or redirect (not 403)
        $I->assertNotEquals(
            403,
            $I->grabHttpResponseCode(),
            'SUPER_ADMIN should be able to access approve route'
        );
    }

    public function regularUserCannotAccessApproveLocationRoute(FunctionalTester $I): void
    {
        $I->wantTo('verify that regular users cannot access the location approve route');

        $user = UserFactory::createApprovedUser();

        $location = $I->createPendingLocation($user);

        $I->amLoggedInAs($user);

        $I->sendPOST($I->getUrl('backoffice_location_approve', ['id' => $location->getId()]));

        $I->seeResponseCodeIs(403);
    }

    public function superAdminCanAccessRejectLocationRoute(FunctionalTester $I): void
    {
        $I->wantTo('verify that super admins can access the location reject route');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);

        $I->amLoggedInAs($superAdmin);

        $I->sendPOST(
            $I->getUrl('backoffice_location_reject', ['id' => $location->getId()]),
            ['reason' => 'Test rejection']
        );

        // Should be successful or redirect (not 403)
        $I->assertNotEquals(
            403,
            $I->grabHttpResponseCode(),
            'SUPER_ADMIN should be able to access reject route'
        );
    }

    public function regularUserCannotAccessRejectLocationRoute(FunctionalTester $I): void
    {
        $I->wantTo('verify that regular users cannot access the location reject route');

        $user = UserFactory::createApprovedUser();

        $location = $I->createPendingLocation($user);

        $I->amLoggedInAs($user);

        $I->sendPOST(
            $I->getUrl('backoffice_location_reject', ['id' => $location->getId()]),
            ['reason' => 'Test rejection']
        );

        $I->seeResponseCodeIs(403);
    }

    public function pendingUserCannotCreateLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that pending users cannot create locations');

        $pendingUser = UserFactory::createPendingUser();

        $I->amLoggedInAs($pendingUser);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canCreate = $authChecker->isGranted('CREATE_LOCATION');

        $I->assertFalse($canCreate, 'PENDING user should not be able to create locations');
    }

    public function approvedUserCanCreateLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that approved users can create locations');

        $approvedUser = UserFactory::createApprovedUser();

        $I->amLoggedInAs($approvedUser);

        $authChecker = $I->getContainer()->get('security.authorization_checker');
        $canCreate = $authChecker->isGranted('CREATE_LOCATION');

        $I->assertTrue($canCreate, 'APPROVED user should be able to create locations');
    }
}
