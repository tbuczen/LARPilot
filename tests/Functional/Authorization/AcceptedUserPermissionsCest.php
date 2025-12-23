<?php

declare(strict_types=1);

namespace Tests\Functional\Authorization;

use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\FunctionalTester;

/**
 * Tests that APPROVED users can create LARPs and Locations with proper limits
 *
 * Covers:
 * - APPROVED users can access LARP creation
 * - APPROVED users can create LARPs within plan limits
 * - Free tier users limited to 1 LARP
 * - Premium plan users respect maxLarps limit
 * - Unlimited plan users can create multiple LARPs
 * - SUPER_ADMIN bypass plan limits
 * - New LARPs default to DRAFT status
 * - APPROVED users can create Locations
 * - Created Locations default to PENDING status
 * - SUPER_ADMIN Locations are auto-approved
 */
class AcceptedUserPermissionsCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
    }

    public function approvedUserCanAccessLarpCreationForm(FunctionalTester $I): void
    {
        $I->wantTo('verify APPROVED user can access LARP creation form');

        $approvedUser = UserFactory::createApprovedUser();

        $I->amLoggedInAs($approvedUser);

        $I->amOnRoute('backoffice_larp_create');

        $I->seeResponseCodeIsSuccessful();
    }

    public function approvedUserCanCreateFirstLarp(FunctionalTester $I): void
    {
        $I->wantTo('verify APPROVED user can create their first LARP');

        $approvedUser = UserFactory::createApprovedUser();

        // User should be able to create their first LARP (free tier default)
        $I->amLoggedInAs($approvedUser);

        $authChecker = $I->grabService('security.authorization_checker');
        $canCreate = $authChecker->isGranted('CREATE_LARP');

        $I->assertTrue($canCreate, 'APPROVED user should be able to create their first LARP');
    }

    public function newLarpDefaultsToDraftStatus(FunctionalTester $I): void
    {
        $I->wantTo('verify new LARP defaults to DRAFT status');

        $approvedUser = UserFactory::createApprovedUser();

        $larp = $I->createLarp($approvedUser);

        $I->assertEquals(
            LarpStageStatus::DRAFT,
            $larp->getStatus(),
            'New LARP should default to DRAFT status'
        );
    }

    public function freeTierUserCannotCreateSecondLarp(FunctionalTester $I): void
    {
        $I->wantTo('verify free tier user cannot create second LARP');

        $approvedUser = UserFactory::createApprovedUser();

        // Create first LARP (should succeed)
        $firstLarp = LarpFactory::createDraftLarp($approvedUser, 'First LARP');

        $I->assertNotNull($firstLarp, 'First LARP should be created successfully');

        // Try to create second LARP (should fail for free tier)
        $I->amLoggedInAs($approvedUser);

        $authChecker = $I->grabService('security.authorization_checker');
        $canCreateSecond = $authChecker->isGranted('CREATE_LARP');

        $I->assertFalse(
            $canCreateSecond,
            'Free tier user should not be able to create second LARP'
        );
    }

    public function freeTierUserWithPlanCanCreateOneLarp(FunctionalTester $I): void
    {
        $I->wantTo('verify free plan user can create 1 LARP');

        $freePlan = $I->createFreePlan();
        $approvedUser = $I->createApprovedUser(null, $freePlan);

        $I->amLoggedInAs($approvedUser);

        $authChecker = $I->grabService('security.authorization_checker');
        $canCreate = $authChecker->isGranted('CREATE_LARP');

        $I->assertTrue($canCreate, 'Free plan user should be able to create 1 LARP');

        // Create the LARP
        $larp = LarpFactory::createDraftLarp($approvedUser);
        $I->assertNotNull($larp);

        // Verify cannot create second
        $I->getEntityManager()->clear(); // Refresh to get updated counts
        $I->amLoggedInAs($approvedUser);
        $canCreateSecond = $authChecker->isGranted('CREATE_LARP');

        $I->assertFalse($canCreateSecond, 'Free plan user should not create second LARP');
    }

    public function premiumPlanUserRespectsMaxLarpsLimit(FunctionalTester $I): void
    {
        $I->wantTo('verify premium plan user respects maxLarps limit');

        $premiumPlan = $I->createPremiumPlan(3); // Max 3 LARPs
        $approvedUser = $I->createApprovedUser(null, $premiumPlan);

        // Create 3 LARPs
        $larp1 = LarpFactory::createDraftLarp($approvedUser, 'LARP 1');
        $larp2 = LarpFactory::createDraftLarp($approvedUser, 'LARP 2');
        $larp3 = LarpFactory::createDraftLarp($approvedUser, 'LARP 3');

        $I->assertNotNull($larp1);
        $I->assertNotNull($larp2);
        $I->assertNotNull($larp3);

        // Verify cannot create 4th LARP
        $I->amLoggedInAs($approvedUser);

        $I->getEntityManager()->clear();
        $I->amLoggedInAs($approvedUser);

        $authChecker = $I->grabService('security.authorization_checker');
        $canCreateFourth = $authChecker->isGranted('CREATE_LARP');

        $I->assertFalse($canCreateFourth, 'Premium plan user should not exceed maxLarps limit');
    }

    public function unlimitedPlanUserCanCreateMultipleLarps(FunctionalTester $I): void
    {
        $I->wantTo('verify unlimited plan user can create multiple LARPs');

        $unlimitedPlan = $I->createUnlimitedPlan();
        $approvedUser = $I->createApprovedUser(null, $unlimitedPlan);

        // Create multiple LARPs
        $larp1 = LarpFactory::createDraftLarp($approvedUser, 'LARP 1');
        $larp2 = LarpFactory::createDraftLarp($approvedUser, 'LARP 2');
        $larp3 = LarpFactory::createDraftLarp($approvedUser, 'LARP 3');
        $larp4 = LarpFactory::createDraftLarp($approvedUser, 'LARP 4');
        $larp5 = LarpFactory::createDraftLarp($approvedUser, 'LARP 5');

        $I->assertNotNull($larp1);
        $I->assertNotNull($larp2);
        $I->assertNotNull($larp3);
        $I->assertNotNull($larp4);
        $I->assertNotNull($larp5);

        // Verify can still create more
        $I->amLoggedInAs($approvedUser);

        $authChecker = $I->grabService('security.authorization_checker');
        $canCreateMore = $authChecker->isGranted('CREATE_LARP');

        $I->assertTrue($canCreateMore, 'Unlimited plan user should be able to create more LARPs');
    }

    public function superAdminBypassesPlanLimits(FunctionalTester $I): void
    {
        $I->wantTo('verify SUPER_ADMIN bypasses plan limits');

        $superAdmin = $I->createSuperAdmin();

        // Create multiple LARPs without a plan
        $larp1 = LarpFactory::createDraftLarp($superAdmin, 'Admin LARP 1');
        $larp2 = LarpFactory::createDraftLarp($superAdmin, 'Admin LARP 2');
        $larp3 = LarpFactory::createDraftLarp($superAdmin, 'Admin LARP 3');

        $I->assertNotNull($larp1);
        $I->assertNotNull($larp2);
        $I->assertNotNull($larp3);

        // Verify can still create more
        $I->amLoggedInAs($superAdmin);

        $authChecker = $I->grabService('security.authorization_checker');
        $canCreateMore = $authChecker->isGranted('CREATE_LARP');

        $I->assertTrue($canCreateMore, 'SUPER_ADMIN should bypass all plan limits');
    }

    public function approvedUserCanAccessLocationCreationForm(FunctionalTester $I): void
    {
        $I->wantTo('verify APPROVED user can access Location creation form');

        $approvedUser = UserFactory::createApprovedUser();

        $I->amLoggedInAs($approvedUser);

        $I->amOnRoute('backoffice_location_modify_global', ['location' => 'new']);

        $I->seeResponseCodeIsSuccessful();
    }

    public function approvedUserCanCreateLocation(FunctionalTester $I): void
    {
        $I->wantTo('verify APPROVED user can create Location');

        $approvedUser = UserFactory::createApprovedUser();

        $location = $I->createLocation($approvedUser);

        $I->assertNotNull($location, 'APPROVED user should be able to create Location');
        $I->assertEquals($approvedUser, $location->getCreatedBy());
    }

    public function createdLocationDefaultsToPendingStatus(FunctionalTester $I): void
    {
        $I->wantTo('verify created Location defaults to PENDING status');

        $approvedUser = UserFactory::createApprovedUser();

        $location = $I->createLocation($approvedUser);

        $I->assertEquals(
            LocationApprovalStatus::PENDING,
            $location->getApprovalStatus(),
            'Created Location should default to PENDING status for regular users'
        );
    }

    public function superAdminLocationIsAutoApproved(FunctionalTester $I): void
    {
        $I->wantTo('verify SUPER_ADMIN Location is auto-approved');

        $superAdmin = $I->createSuperAdmin();

        // Use LocationApprovalService to create location (simulating real flow)
        $locationApprovalService = $I->grabService(
            \App\Domain\Core\Service\LocationApprovalService::class
        );

        $location = $I->createLocation($superAdmin);

        // Auto-approve it
        $locationApprovalService->autoApprove($location, $superAdmin);

        $I->assertEquals(
            LocationApprovalStatus::APPROVED,
            $location->getApprovalStatus(),
            'SUPER_ADMIN Location should be auto-approved'
        );
        $I->assertEquals($superAdmin, $location->getApprovedBy());
        $I->assertNotNull($location->getApprovedAt());
    }

    public function approvedUserCanCreateMultipleLocations(FunctionalTester $I): void
    {
        $I->wantTo('verify APPROVED user can create multiple Locations');

        $approvedUser = UserFactory::createApprovedUser();

        $location1 = $I->createLocation($approvedUser, LocationApprovalStatus::PENDING, 'Location 1');
        $location2 = $I->createLocation($approvedUser, LocationApprovalStatus::PENDING, 'Location 2');
        $location3 = $I->createLocation($approvedUser, LocationApprovalStatus::PENDING, 'Location 3');

        $I->assertNotNull($location1);
        $I->assertNotNull($location2);
        $I->assertNotNull($location3);

        // Verify all are PENDING
        $I->assertEquals(LocationApprovalStatus::PENDING, $location1->getApprovalStatus());
        $I->assertEquals(LocationApprovalStatus::PENDING, $location2->getApprovalStatus());
        $I->assertEquals(LocationApprovalStatus::PENDING, $location3->getApprovalStatus());
    }

    public function larpCreationSetsOrganizerAsParticipant(FunctionalTester $I): void
    {
        $I->wantTo('verify LARP creation sets organizer as participant');

        $approvedUser = UserFactory::createApprovedUser();

        $larp = LarpFactory::createDraftLarp($approvedUser);

        // Verify user is added as participant with ORGANIZER role
        $participants = $larp->getLarpParticipants();
        $I->assertCount(1, $participants, 'LARP should have 1 participant (organizer)');

        $participant = $participants[0];
        $I->assertEquals($approvedUser, $participant->getUser());
        $I->assertTrue(
            $participant->isOrganizer(),
            'Creator should have ORGANIZER role'
        );
    }

    public function userLarpCountReflectsOrganizerRole(FunctionalTester $I): void
    {
        $I->wantTo('verify user LARP count reflects organizer role');

        $approvedUser = UserFactory::createApprovedUser();

        $initialCount = $approvedUser->getOrganizerLarpCount();

        $larp1 = LarpFactory::createDraftLarp($approvedUser);

        // Clear and reload to get fresh count
        $I->getEntityManager()->clear();
        $reloadedUser = $I->getEntityManager()->find(
            \App\Domain\Account\Entity\User::class,
            $approvedUser->getId()
        );

        $newCount = $reloadedUser->getOrganizerLarpCount();

        $I->assertEquals(
            $initialCount + 1,
            $newCount,
            'Organizer LARP count should increase after creating LARP'
        );
    }
}
