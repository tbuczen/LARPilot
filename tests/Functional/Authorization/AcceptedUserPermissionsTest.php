<?php

declare(strict_types=1);

namespace App\Tests\Functional\Authorization;

use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use App\Tests\Traits\AuthenticationTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
class AcceptedUserPermissionsTest extends WebTestCase
{
    use AuthenticationTestTrait;

    private KernelBrowser $client;
    
    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
    }
    

    public function test_approved_user_can_access_larp_creation_form(): void
    {
        $approvedUser = $this->createApprovedUser();

        $this->client->loginUser($approvedUser);

        $this->client->request('GET', $this->generateUrl('backoffice_larp_create'));

        $this->assertResponseIsSuccessful('APPROVED user should access LARP creation form');
    }

    public function test_approved_user_can_create_first_larp(): void
    {
        $approvedUser = $this->createApprovedUser();

        // User should be able to create their first LARP (free tier default)
        $this->client->loginUser($approvedUser);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canCreate = $authChecker->isGranted('CREATE_LARP');

        $this->assertTrue($canCreate, 'APPROVED user should be able to create their first LARP');
    }

    public function test_new_larp_defaults_to_draft_status(): void
    {
        $approvedUser = $this->createApprovedUser();

        $larp = $this->createLarp($approvedUser);

        $this->assertEquals(
            LarpStageStatus::DRAFT,
            $larp->getStatus(),
            'New LARP should default to DRAFT status'
        );
    }

    public function test_free_tier_user_cannot_create_second_larp(): void
    {
        $approvedUser = $this->createApprovedUser();

        // Create first LARP (should succeed)
        $firstLarp = $this->createDraftLarp($approvedUser, 'First LARP');

        $this->assertNotNull($firstLarp, 'First LARP should be created successfully');

        // Try to create second LARP (should fail for free tier)
        $this->client->loginUser($approvedUser);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canCreateSecond = $authChecker->isGranted('CREATE_LARP');

        $this->assertFalse(
            $canCreateSecond,
            'Free tier user should not be able to create second LARP'
        );
    }

    public function test_free_tier_user_with_plan_can_create_one_larp(): void
    {
        $freePlan = $this->createFreePlan();
        $approvedUser = $this->createApprovedUser(null, $freePlan);

        $this->client->loginUser($approvedUser);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canCreate = $authChecker->isGranted('CREATE_LARP');

        $this->assertTrue($canCreate, 'Free plan user should be able to create 1 LARP');

        // Create the LARP
        $larp = $this->createDraftLarp($approvedUser);
        $this->assertNotNull($larp);

        // Verify cannot create second
        $this->getEntityManager()->clear(); // Refresh to get updated counts
        $this->client->loginUser($approvedUser);
        $canCreateSecond = $authChecker->isGranted('CREATE_LARP');

        $this->assertFalse($canCreateSecond, 'Free plan user should not create second LARP');
    }

    public function test_premium_plan_user_respects_max_larps_limit(): void
    {
        $premiumPlan = $this->createPremiumPlan(3); // Max 3 LARPs
        $approvedUser = $this->createApprovedUser(null, $premiumPlan);

        // Create 3 LARPs
        $larp1 = $this->createDraftLarp($approvedUser, 'LARP 1');
        $larp2 = $this->createDraftLarp($approvedUser, 'LARP 2');
        $larp3 = $this->createDraftLarp($approvedUser, 'LARP 3');

        $this->assertNotNull($larp1);
        $this->assertNotNull($larp2);
        $this->assertNotNull($larp3);

        // Verify cannot create 4th LARP
        $this->client->loginUser($approvedUser);

        $this->getEntityManager()->clear();
        $this->client->loginUser($approvedUser);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canCreateFourth = $authChecker->isGranted('CREATE_LARP');

        $this->assertFalse($canCreateFourth, 'Premium plan user should not exceed maxLarps limit');
    }

    public function test_unlimited_plan_user_can_create_multiple_larps(): void
    {
        $unlimitedPlan = $this->createUnlimitedPlan();
        $approvedUser = $this->createApprovedUser(null, $unlimitedPlan);

        // Create multiple LARPs
        $larp1 = $this->createDraftLarp($approvedUser, 'LARP 1');
        $larp2 = $this->createDraftLarp($approvedUser, 'LARP 2');
        $larp3 = $this->createDraftLarp($approvedUser, 'LARP 3');
        $larp4 = $this->createDraftLarp($approvedUser, 'LARP 4');
        $larp5 = $this->createDraftLarp($approvedUser, 'LARP 5');

        $this->assertNotNull($larp1);
        $this->assertNotNull($larp2);
        $this->assertNotNull($larp3);
        $this->assertNotNull($larp4);
        $this->assertNotNull($larp5);

        // Verify can still create more
        $this->client->loginUser($approvedUser);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canCreateMore = $authChecker->isGranted('CREATE_LARP');

        $this->assertTrue($canCreateMore, 'Unlimited plan user should be able to create more LARPs');
    }

    public function test_super_admin_bypasses_plan_limits(): void
    {
        $superAdmin = $this->createSuperAdmin();

        // Create multiple LARPs without a plan
        $larp1 = $this->createDraftLarp($superAdmin, 'Admin LARP 1');
        $larp2 = $this->createDraftLarp($superAdmin, 'Admin LARP 2');
        $larp3 = $this->createDraftLarp($superAdmin, 'Admin LARP 3');

        $this->assertNotNull($larp1);
        $this->assertNotNull($larp2);
        $this->assertNotNull($larp3);

        // Verify can still create more
        $this->client->loginUser($superAdmin);

        $authChecker = static::getContainer()->get('security.authorization_checker');
        $canCreateMore = $authChecker->isGranted('CREATE_LARP');

        $this->assertTrue($canCreateMore, 'SUPER_ADMIN should bypass all plan limits');
    }

    public function test_approved_user_can_access_location_creation_form(): void
    {
        $approvedUser = $this->createApprovedUser();

        $this->client->loginUser($approvedUser);

        $this->client->request('GET', $this->generateUrl('backoffice_location_modify_global', ['location' => 'new']));

        $this->assertResponseIsSuccessful('APPROVED user should access Location creation form');
    }

    public function test_approved_user_can_create_location(): void
    {
        $approvedUser = $this->createApprovedUser();

        $location = $this->createLocation($approvedUser);

        $this->assertNotNull($location, 'APPROVED user should be able to create Location');
        $this->assertEquals($approvedUser, $location->getCreatedBy());
    }

    public function test_created_location_defaults_to_pending_status(): void
    {
        $approvedUser = $this->createApprovedUser();

        $location = $this->createLocation($approvedUser);

        $this->assertEquals(
            LocationApprovalStatus::PENDING,
            $location->getApprovalStatus(),
            'Created Location should default to PENDING status for regular users'
        );
    }

    public function test_super_admin_location_is_auto_approved(): void
    {
        $superAdmin = $this->createSuperAdmin();

        // Use LocationApprovalService to create location (simulating real flow)
        $locationApprovalService = static::getContainer()->get(
            \App\Domain\Core\Service\LocationApprovalService::class
        );

        $location = $this->createLocation($superAdmin);

        // Auto-approve it
        $locationApprovalService->autoApprove($location, $superAdmin);

        $this->assertEquals(
            LocationApprovalStatus::APPROVED,
            $location->getApprovalStatus(),
            'SUPER_ADMIN Location should be auto-approved'
        );
        $this->assertEquals($superAdmin, $location->getApprovedBy());
        $this->assertNotNull($location->getApprovedAt());
    }

    public function test_approved_user_can_create_multiple_locations(): void
    {
        $approvedUser = $this->createApprovedUser();

        $location1 = $this->createLocation($approvedUser, LocationApprovalStatus::PENDING, 'Location 1');
        $location2 = $this->createLocation($approvedUser, LocationApprovalStatus::PENDING, 'Location 2');
        $location3 = $this->createLocation($approvedUser, LocationApprovalStatus::PENDING, 'Location 3');

        $this->assertNotNull($location1);
        $this->assertNotNull($location2);
        $this->assertNotNull($location3);

        // Verify all are PENDING
        $this->assertEquals(LocationApprovalStatus::PENDING, $location1->getApprovalStatus());
        $this->assertEquals(LocationApprovalStatus::PENDING, $location2->getApprovalStatus());
        $this->assertEquals(LocationApprovalStatus::PENDING, $location3->getApprovalStatus());
    }

    public function test_larp_creation_sets_organizer_as_participant(): void
    {
        $approvedUser = $this->createApprovedUser();

        $larp = $this->createDraftLarp($approvedUser);

        // Verify user is added as participant with ORGANIZER role
        $participants = $larp->getLarpParticipants();
        $this->assertCount(1, $participants, 'LARP should have 1 participant (organizer)');

        $participant = $participants[0];
        $this->assertEquals($approvedUser, $participant->getUser());
        $this->assertTrue(
            $participant->isOrganizer(),
            'Creator should have ORGANIZER role'
        );
    }

    public function test_user_larp_count_reflects_organizer_role(): void
    {
        $approvedUser = $this->createApprovedUser();

        $initialCount = $approvedUser->getOrganizerLarpCount();

        $larp1 = $this->createDraftLarp($approvedUser);

        // Clear and reload to get fresh count
        $this->getEntityManager()->clear();
        $reloadedUser = $this->getEntityManager()->find(
            \App\Domain\Account\Entity\User::class,
            $approvedUser->getId()
        );

        $newCount = $reloadedUser->getOrganizerLarpCount();

        $this->assertEquals(
            $initialCount + 1,
            $newCount,
            'Organizer LARP count should increase after creating LARP'
        );
    }
}
