<?php

declare(strict_types=1);

namespace Tests\Integration\Service;

use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use App\Domain\Core\Service\LocationApprovalService;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\FunctionalTester;

/**
 * Integration tests for LocationApprovalService
 *
 * Tests service layer business logic for location approval workflow
 */
class LocationApprovalServiceCest
{
    private ?LocationApprovalService $locationApprovalService = null;

    public function _before(FunctionalTester $I): void
    {
        $this->locationApprovalService = $I->grabService(LocationApprovalService::class);
    }

    public function canUserCreateLocationReturnsFalseForPendingUser(FunctionalTester $I): void
    {
        $I->wantTo('verify that PENDING user cannot create locations');

        $pendingUser = $I->createPendingUser();

        $canCreate = $this->locationApprovalService->canUserCreateLocation($pendingUser);

        $I->assertFalse(
            $canCreate,
            'PENDING user should not be able to create locations'
        );
    }

    public function canUserCreateLocationReturnsTrueForApprovedUser(FunctionalTester $I): void
    {
        $I->wantTo('verify that APPROVED user can create locations');

        $approvedUser = UserFactory::createApprovedUser();

        $canCreate = $this->locationApprovalService->canUserCreateLocation($approvedUser);

        $I->assertTrue(
            $canCreate,
            'APPROVED user should be able to create locations'
        );
    }

    public function canUserCreateLocationReturnsFalseForSuspendedUser(FunctionalTester $I): void
    {
        $I->wantTo('verify that SUSPENDED user cannot create locations');

        $suspendedUser = $I->createSuspendedUser();

        $canCreate = $this->locationApprovalService->canUserCreateLocation($suspendedUser);

        $I->assertFalse(
            $canCreate,
            'SUSPENDED user should not be able to create locations'
        );
    }

    public function canUserCreateLocationReturnsFalseForBannedUser(FunctionalTester $I): void
    {
        $I->wantTo('verify that BANNED user cannot create locations');

        $bannedUser = $I->createBannedUser();

        $canCreate = $this->locationApprovalService->canUserCreateLocation($bannedUser);

        $I->assertFalse(
            $canCreate,
            'BANNED user should not be able to create locations'
        );
    }

    public function canUserCreateLocationReturnsTrueForSuperAdmin(FunctionalTester $I): void
    {
        $I->wantTo('verify that SUPER_ADMIN can create locations');

        $superAdmin = $I->createSuperAdmin();

        $canCreate = $this->locationApprovalService->canUserCreateLocation($superAdmin);

        $I->assertTrue(
            $canCreate,
            'SUPER_ADMIN should be able to create locations'
        );
    }

    public function approveUpdatesLocationStatusCorrectly(FunctionalTester $I): void
    {
        $I->wantTo('verify that approve method updates location status correctly');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);

        $I->assertEquals(LocationApprovalStatus::PENDING, $location->getApprovalStatus());

        $this->locationApprovalService->approve($location, $superAdmin);

        $I->assertEquals(LocationApprovalStatus::APPROVED, $location->getApprovalStatus());
        $I->assertEquals($superAdmin, $location->getApprovedBy());
        $I->assertInstanceOf(\DateTimeInterface::class, $location->getApprovedAt());
        $I->assertNull($location->getRejectionReason());
    }

    public function approveSetsApprovedAtTimestamp(FunctionalTester $I): void
    {
        $I->wantTo('verify that approve method sets approvedAt timestamp');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);

        $beforeApproval = new \DateTime();

        $this->locationApprovalService->approve($location, $superAdmin);

        $afterApproval = new \DateTime();

        $I->assertNotNull($location->getApprovedAt());
        $I->assertGreaterThanOrEqual(
            $beforeApproval->getTimestamp(),
            $location->getApprovedAt()->getTimestamp()
        );
        $I->assertLessThanOrEqual(
            $afterApproval->getTimestamp(),
            $location->getApprovedAt()->getTimestamp()
        );
    }

    public function rejectUpdatesLocationStatusCorrectly(FunctionalTester $I): void
    {
        $I->wantTo('verify that reject method updates location status correctly');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);

        $rejectionReason = 'Invalid address provided';

        $this->locationApprovalService->reject($location, $superAdmin, $rejectionReason);

        $I->assertEquals(LocationApprovalStatus::REJECTED, $location->getApprovalStatus());
        $I->assertEquals($rejectionReason, $location->getRejectionReason());
        $I->assertNull($location->getApprovedBy());
        $I->assertNull($location->getApprovedAt());
    }

    public function rejectWithNullReason(FunctionalTester $I): void
    {
        $I->wantTo('verify that reject method works with null reason');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);

        $this->locationApprovalService->reject($location, $superAdmin, null);

        $I->assertEquals(LocationApprovalStatus::REJECTED, $location->getApprovalStatus());
        $I->assertNull($location->getRejectionReason());
    }

    public function rejectClearsPreviousApproval(FunctionalTester $I): void
    {
        $I->wantTo('verify that reject method clears previous approval');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);

        // First approve
        $this->locationApprovalService->approve($location, $superAdmin);

        $I->assertEquals(LocationApprovalStatus::APPROVED, $location->getApprovalStatus());
        $I->assertNotNull($location->getApprovedBy());
        $I->assertNotNull($location->getApprovedAt());

        // Then reject
        $this->locationApprovalService->reject($location, $superAdmin, 'Changed mind');

        $I->assertEquals(LocationApprovalStatus::REJECTED, $location->getApprovalStatus());
        $I->assertNull($location->getApprovedBy());
        $I->assertNull($location->getApprovedAt());
        $I->assertEquals('Changed mind', $location->getRejectionReason());
    }

    public function autoApproveSetsCorrectStatusAndApprover(FunctionalTester $I): void
    {
        $I->wantTo('verify that autoApprove method sets correct status and approver');

        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($superAdmin);

        $this->locationApprovalService->autoApprove($location, $superAdmin);

        $I->assertEquals(LocationApprovalStatus::APPROVED, $location->getApprovalStatus());
        $I->assertEquals($superAdmin, $location->getApprovedBy());
        $I->assertNotNull($location->getApprovedAt());
    }

    public function autoApproveOnlyForSuperAdmin(FunctionalTester $I): void
    {
        $I->wantTo('verify that autoApprove only works for SUPER_ADMIN');

        $superAdmin = $I->createSuperAdmin();

        $location = $I->createLocation($superAdmin);

        $this->locationApprovalService->autoApprove($location, $superAdmin);

        $I->assertEquals(
            LocationApprovalStatus::APPROVED,
            $location->getApprovalStatus(),
            'SUPER_ADMIN location should be auto-approved'
        );
    }

    public function canUserEditLocationReturnsTrueForOwnPendingLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that user can edit their own PENDING location');

        $user = UserFactory::createApprovedUser();
        $location = $I->createPendingLocation($user);

        $canEdit = $this->locationApprovalService->canUserEditLocation($user, $location);

        $I->assertTrue(
            $canEdit,
            'User should be able to edit their own PENDING location'
        );
    }

    public function canUserEditLocationReturnsTrueForOwnRejectedLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that user can edit their own REJECTED location');

        $user = UserFactory::createApprovedUser();
        $location = $I->createRejectedLocation($user);

        $canEdit = $this->locationApprovalService->canUserEditLocation($user, $location);

        $I->assertTrue(
            $canEdit,
            'User should be able to edit their own REJECTED location'
        );
    }

    public function canUserEditLocationReturnsFalseForOwnApprovedLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that user cannot edit their own APPROVED location');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);
        $this->locationApprovalService->approve($location, $superAdmin);

        $canEdit = $this->locationApprovalService->canUserEditLocation($user, $location);

        $I->assertFalse(
            $canEdit,
            'User should not be able to edit their own APPROVED location'
        );
    }

    public function canUserEditLocationReturnsFalseForOtherUsersLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify that user cannot edit other user\'s location');

        $user1 = $I->createApprovedUser('user1@example.com');
        $user2 = $I->createApprovedUser('user2@example.com');

        $location = $I->createPendingLocation($user1);

        $canEdit = $this->locationApprovalService->canUserEditLocation($user2, $location);

        $I->assertFalse(
            $canEdit,
            'User should not be able to edit other user\'s location'
        );
    }

    public function canUserEditLocationReturnsTrueForSuperAdmin(FunctionalTester $I): void
    {
        $I->wantTo('verify that SUPER_ADMIN can edit any location');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createApprovedLocation($user);

        $canEdit = $this->locationApprovalService->canUserEditLocation($superAdmin, $location);

        $I->assertTrue(
            $canEdit,
            'SUPER_ADMIN should be able to edit any location'
        );
    }

    public function approvalChangesPersistToDatabase(FunctionalTester $I): void
    {
        $I->wantTo('verify that approval changes persist to database');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);
        $locationId = $location->getId();

        $this->locationApprovalService->approve($location, $superAdmin);

        // Clear entity manager to force reload from database
        $I->getEntityManager()->clear();

        $reloadedLocation = $I->getEntityManager()->find(
            \App\Domain\Core\Entity\Location::class,
            $locationId
        );

        $I->assertNotNull($reloadedLocation);
        $I->assertEquals(
            LocationApprovalStatus::APPROVED,
            $reloadedLocation->getApprovalStatus(),
            'Approval should persist in database'
        );
        $I->assertNotNull($reloadedLocation->getApprovedBy());
        $I->assertNotNull($reloadedLocation->getApprovedAt());
    }

    public function rejectionChangesPersistToDatabase(FunctionalTester $I): void
    {
        $I->wantTo('verify that rejection changes persist to database');

        $user = UserFactory::createApprovedUser();
        $superAdmin = $I->createSuperAdmin();

        $location = $I->createPendingLocation($user);
        $locationId = $location->getId();

        $this->locationApprovalService->reject($location, $superAdmin, 'Test reason');

        // Clear entity manager to force reload from database
        $I->getEntityManager()->clear();

        $reloadedLocation = $I->getEntityManager()->find(
            \App\Domain\Core\Entity\Location::class,
            $locationId
        );

        $I->assertNotNull($reloadedLocation);
        $I->assertEquals(
            LocationApprovalStatus::REJECTED,
            $reloadedLocation->getApprovalStatus(),
            'Rejection should persist in database'
        );
        $I->assertEquals('Test reason', $reloadedLocation->getRejectionReason());
    }
}
