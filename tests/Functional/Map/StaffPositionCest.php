<?php

declare(strict_types=1);

namespace Tests\Functional\Map;

use App\Domain\Map\Security\Voter\StaffPositionVoter;
use App\Domain\Map\Service\StaffPositionService;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\Map\GameMapFactory;
use Tests\Support\Factory\Map\StaffPositionFactory;
use Tests\Support\FunctionalTester;

/**
 * Tests Staff Position tracking feature
 *
 * Covers:
 * - Access control: Only non-player participants can update positions
 * - Visibility: Organizers/staff see all, players see limited roles
 * - Position CRUD operations
 * - Grid cell validation
 */
class StaffPositionCest
{
    // ========================================================================
    // Access Control Tests
    // ========================================================================

    public function organizerCanUpdatePosition(FunctionalTester $I): void
    {
        $I->wantTo('verify that organizers can update their position');

        $user = UserFactory::createApprovedUser();
        $larp = LarpFactory::new()->create();
        $participant = LarpParticipantFactory::new()
            ->forLarp($larp->_real())
            ->forUser($user)
            ->organizer()
            ->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->create();

        $I->amLoggedInAs($user);

        $authChecker = $I->grabService('security.authorization_checker');
        $canUpdate = $authChecker->isGranted(StaffPositionVoter::UPDATE_POSITION, $larp->_real());

        $I->assertTrue($canUpdate, 'Organizer should be able to update position');
    }

    public function staffCanUpdatePosition(FunctionalTester $I): void
    {
        $I->wantTo('verify that staff can update their position');

        $user = UserFactory::createApprovedUser();
        $larp = LarpFactory::new()->create();
        $participant = LarpParticipantFactory::new()
            ->forLarp($larp->_real())
            ->forUser($user)
            ->staff()
            ->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->create();

        $I->amLoggedInAs($user);

        $authChecker = $I->grabService('security.authorization_checker');
        $canUpdate = $authChecker->isGranted(StaffPositionVoter::UPDATE_POSITION, $larp->_real());

        $I->assertTrue($canUpdate, 'Staff should be able to update position');
    }

    public function gameMasterCanUpdatePosition(FunctionalTester $I): void
    {
        $I->wantTo('verify that game masters can update their position');

        $user = UserFactory::createApprovedUser();
        $larp = LarpFactory::new()->create();
        $participant = LarpParticipantFactory::new()
            ->forLarp($larp->_real())
            ->forUser($user)
            ->gameMaster()
            ->create();

        $I->amLoggedInAs($user);

        $authChecker = $I->grabService('security.authorization_checker');
        $canUpdate = $authChecker->isGranted(StaffPositionVoter::UPDATE_POSITION, $larp->_real());

        $I->assertTrue($canUpdate, 'Game master should be able to update position');
    }

    public function playerCannotUpdatePosition(FunctionalTester $I): void
    {
        $I->wantTo('verify that players cannot update position');

        $user = UserFactory::createApprovedUser();
        $larp = LarpFactory::new()->create();
        $participant = LarpParticipantFactory::new()
            ->forLarp($larp->_real())
            ->forUser($user)
            ->player()
            ->create();

        $I->amLoggedInAs($user);

        $authChecker = $I->grabService('security.authorization_checker');
        $canUpdate = $authChecker->isGranted(StaffPositionVoter::UPDATE_POSITION, $larp->_real());

        $I->assertFalse($canUpdate, 'Player should not be able to update position');
    }

    public function nonParticipantCannotAccessPositions(FunctionalTester $I): void
    {
        $I->wantTo('verify that non-participants cannot access positions');

        $user = UserFactory::createApprovedUser();
        $larp = LarpFactory::new()->create();
        // User is not a participant in this LARP

        $I->amLoggedInAs($user);

        $authChecker = $I->grabService('security.authorization_checker');
        $canUpdate = $authChecker->isGranted(StaffPositionVoter::UPDATE_POSITION, $larp->_real());

        $I->assertFalse($canUpdate, 'Non-participant should not be able to update position');
    }

    // ========================================================================
    // Visibility Tests
    // ========================================================================

    public function organizerSeesAllStaffPositions(FunctionalTester $I): void
    {
        $I->wantTo('verify that organizers see all staff positions');

        $larp = LarpFactory::new()->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->create();

        // Create various staff positions
        $organizer = LarpParticipantFactory::new()->forLarp($larp->_real())->organizer()->create();
        $gameMaster = LarpParticipantFactory::new()->forLarp($larp->_real())->gameMaster()->create();
        $trustPerson = LarpParticipantFactory::new()->forLarp($larp->_real())->trustPerson()->create();

        StaffPositionFactory::new()->forParticipant($organizer->_real())->forMap($map->_real())->atCell('A1')->create();
        StaffPositionFactory::new()->forParticipant($gameMaster->_real())->forMap($map->_real())->atCell('B2')->create();
        StaffPositionFactory::new()->forParticipant($trustPerson->_real())->forMap($map->_real())->atCell('C3')->create();

        // Create a viewer who is an organizer
        $viewerUser = UserFactory::createApprovedUser();
        $viewer = LarpParticipantFactory::new()
            ->forLarp($larp->_real())
            ->forUser($viewerUser)
            ->organizer()
            ->create();

        /** @var StaffPositionService $service */
        $service = $I->grabService(StaffPositionService::class);
        $visiblePositions = $service->getVisiblePositions($map->_real(), $viewer->_real());

        $I->assertCount(3, $visiblePositions, 'Organizer should see all 3 staff positions');
    }

    public function playerSeesOnlyOrganizerTrustPersonPhotographer(FunctionalTester $I): void
    {
        $I->wantTo('verify that players see only organizer, trust person, and photographer positions');

        $larp = LarpFactory::new()->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->create();

        // Create various staff positions
        $organizer = LarpParticipantFactory::new()->forLarp($larp->_real())->organizer()->create();
        $gameMaster = LarpParticipantFactory::new()->forLarp($larp->_real())->gameMaster()->create();
        $trustPerson = LarpParticipantFactory::new()->forLarp($larp->_real())->trustPerson()->create();
        $photographer = LarpParticipantFactory::new()->forLarp($larp->_real())->photographer()->create();

        StaffPositionFactory::new()->forParticipant($organizer->_real())->forMap($map->_real())->atCell('A1')->create();
        StaffPositionFactory::new()->forParticipant($gameMaster->_real())->forMap($map->_real())->atCell('B2')->create();
        StaffPositionFactory::new()->forParticipant($trustPerson->_real())->forMap($map->_real())->atCell('C3')->create();
        StaffPositionFactory::new()->forParticipant($photographer->_real())->forMap($map->_real())->atCell('D4')->create();

        // Create a viewer who is a player
        $viewerUser = UserFactory::createApprovedUser();
        $viewer = LarpParticipantFactory::new()
            ->forLarp($larp->_real())
            ->forUser($viewerUser)
            ->player()
            ->create();

        /** @var StaffPositionService $service */
        $service = $I->grabService(StaffPositionService::class);
        $visiblePositions = $service->getVisiblePositions($map->_real(), $viewer->_real());

        // Player should see: organizer, trust person, photographer (3), but NOT game master
        $I->assertCount(3, $visiblePositions, 'Player should see only 3 positions (organizer, trust person, photographer)');
    }

    public function playerDoesNotSeeGameMasterPosition(FunctionalTester $I): void
    {
        $I->wantTo('verify that players do not see game master positions');

        $larp = LarpFactory::new()->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->create();

        // Create only a game master position
        $gameMaster = LarpParticipantFactory::new()->forLarp($larp->_real())->gameMaster()->create();
        StaffPositionFactory::new()->forParticipant($gameMaster->_real())->forMap($map->_real())->atCell('A1')->create();

        // Create a viewer who is a player
        $viewerUser = UserFactory::createApprovedUser();
        $viewer = LarpParticipantFactory::new()
            ->forLarp($larp->_real())
            ->forUser($viewerUser)
            ->player()
            ->create();

        /** @var StaffPositionService $service */
        $service = $I->grabService(StaffPositionService::class);
        $visiblePositions = $service->getVisiblePositions($map->_real(), $viewer->_real());

        $I->assertCount(0, $visiblePositions, 'Player should not see game master position');
    }

    // ========================================================================
    // Functionality Tests
    // ========================================================================

    public function canSaveNewPosition(FunctionalTester $I): void
    {
        $I->wantTo('verify that a new position can be saved');

        $larp = LarpFactory::new()->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->withGridSize(10, 10)->create();
        $participant = LarpParticipantFactory::new()->forLarp($larp->_real())->organizer()->create();

        /** @var StaffPositionService $service */
        $service = $I->grabService(StaffPositionService::class);

        $position = $service->updatePosition($participant->_real(), $map->_real(), 'B3', 'On patrol');

        $I->assertNotNull($position->getId());
        $I->assertEquals('B3', $position->getGridCell());
        $I->assertEquals('On patrol', $position->getStatusNote());
    }

    public function canUpdateExistingPosition(FunctionalTester $I): void
    {
        $I->wantTo('verify that an existing position can be updated');

        $larp = LarpFactory::new()->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->withGridSize(10, 10)->create();
        $participant = LarpParticipantFactory::new()->forLarp($larp->_real())->organizer()->create();

        // Create initial position
        $existingPosition = StaffPositionFactory::new()
            ->forParticipant($participant->_real())
            ->forMap($map->_real())
            ->atCell('A1')
            ->create();

        /** @var StaffPositionService $service */
        $service = $I->grabService(StaffPositionService::class);

        // Update to new position
        $updatedPosition = $service->updatePosition($participant->_real(), $map->_real(), 'C5', 'Moved to new area');

        $I->assertEquals($existingPosition->getId(), $updatedPosition->getId());
        $I->assertEquals('C5', $updatedPosition->getGridCell());
        $I->assertEquals('Moved to new area', $updatedPosition->getStatusNote());
    }

    public function invalidGridCellIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('verify that invalid grid cells are rejected');

        $larp = LarpFactory::new()->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->withGridSize(10, 10)->create();
        $participant = LarpParticipantFactory::new()->forLarp($larp->_real())->organizer()->create();

        /** @var StaffPositionService $service */
        $service = $I->grabService(StaffPositionService::class);

        // Try to set position at invalid cell (Z99 is outside 10x10 grid)
        $exceptionThrown = false;
        try {
            $service->updatePosition($participant->_real(), $map->_real(), 'Z99');
        } catch (\InvalidArgumentException $e) {
            $exceptionThrown = true;
        }

        $I->assertTrue($exceptionThrown, 'Invalid grid cell should throw exception');
    }

    public function validGridCellsAccepted(FunctionalTester $I): void
    {
        $I->wantTo('verify that valid grid cells are accepted');

        $larp = LarpFactory::new()->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->withGridSize(10, 10)->create();

        /** @var StaffPositionService $service */
        $service = $I->grabService(StaffPositionService::class);

        // Valid cells for 10x10 grid (A-J columns, 1-10 rows)
        $I->assertTrue($service->validateGridCell($map->_real(), 'A1'));
        $I->assertTrue($service->validateGridCell($map->_real(), 'J10'));
        $I->assertTrue($service->validateGridCell($map->_real(), 'E5'));

        // Invalid cells
        $I->assertFalse($service->validateGridCell($map->_real(), 'K1'));  // Column K doesn't exist
        $I->assertFalse($service->validateGridCell($map->_real(), 'A11')); // Row 11 doesn't exist
        $I->assertFalse($service->validateGridCell($map->_real(), 'K11')); // Both invalid
    }

    public function statusNoteIsSaved(FunctionalTester $I): void
    {
        $I->wantTo('verify that status note is saved correctly');

        $larp = LarpFactory::new()->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->create();
        $participant = LarpParticipantFactory::new()->forLarp($larp->_real())->organizer()->create();

        /** @var StaffPositionService $service */
        $service = $I->grabService(StaffPositionService::class);

        $statusNote = 'Handling incident at tavern area';
        $position = $service->updatePosition($participant->_real(), $map->_real(), 'A1', $statusNote);

        $I->assertEquals($statusNote, $position->getStatusNote());
    }

    public function positionCanBeRemoved(FunctionalTester $I): void
    {
        $I->wantTo('verify that a position can be removed');

        $larp = LarpFactory::new()->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->create();
        $participant = LarpParticipantFactory::new()->forLarp($larp->_real())->organizer()->create();

        StaffPositionFactory::new()
            ->forParticipant($participant->_real())
            ->forMap($map->_real())
            ->atCell('A1')
            ->create();

        /** @var StaffPositionService $service */
        $service = $I->grabService(StaffPositionService::class);

        // Verify position exists
        $I->assertNotNull($service->getPosition($participant->_real(), $map->_real()));

        // Remove position
        $service->removePosition($participant->_real(), $map->_real());

        // Verify position is removed
        $I->assertNull($service->getPosition($participant->_real(), $map->_real()));
    }

    // ========================================================================
    // Route Access Tests
    // ========================================================================

    public function organizerCanAccessUpdatePositionPage(FunctionalTester $I): void
    {
        $I->wantTo('verify that organizers can access the position update page');

        $user = UserFactory::createApprovedUser();
        $larp = LarpFactory::new()->create();
        $participant = LarpParticipantFactory::new()
            ->forLarp($larp->_real())
            ->forUser($user)
            ->organizer()
            ->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->create();

        $I->amLoggedInAs($user);

        $I->amOnRoute('participant_staff_position_update', [
            'larp' => $larp->getId(),
            'map' => $map->getId(),
        ]);

        $I->seeResponseCodeIsSuccessful();
    }

    public function playerCannotAccessUpdatePositionPage(FunctionalTester $I): void
    {
        $I->wantTo('verify that players cannot access the position update page');

        $user = UserFactory::createApprovedUser();
        $larp = LarpFactory::new()->create();
        $participant = LarpParticipantFactory::new()
            ->forLarp($larp->_real())
            ->forUser($user)
            ->player()
            ->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->create();

        $I->amLoggedInAs($user);

        $I->amOnRoute('participant_staff_position_update', [
            'larp' => $larp->getId(),
            'map' => $map->getId(),
        ]);

        $I->seeResponseCodeIs(403);
    }

    public function playerCanAccessViewPositionsPage(FunctionalTester $I): void
    {
        $I->wantTo('verify that players can access the view positions page');

        $user = UserFactory::createApprovedUser();
        $larp = LarpFactory::new()->create();
        $participant = LarpParticipantFactory::new()
            ->forLarp($larp->_real())
            ->forUser($user)
            ->player()
            ->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->create();

        $I->amLoggedInAs($user);

        $I->amOnRoute('participant_staff_position_view', [
            'larp' => $larp->getId(),
            'map' => $map->getId(),
        ]);

        $I->seeResponseCodeIsSuccessful();
    }

    // ========================================================================
    // Backoffice Integration Tests
    // ========================================================================

    public function backofficeMapViewShowsPositionPanelForOrganizer(FunctionalTester $I): void
    {
        $I->wantTo('verify that backoffice map view shows position update panel for organizer');

        $user = UserFactory::createApprovedUser();
        $larp = LarpFactory::new()->create();
        $participant = LarpParticipantFactory::new()
            ->forLarp($larp->_real())
            ->forUser($user)
            ->organizer()
            ->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->withImage('test-map.png')->create();

        $I->amLoggedInAs($user);

        $I->amOnRoute('backoffice_larp_map_view', [
            'larp' => $larp->getId(),
            'map' => $map->getId(),
        ]);

        $I->seeResponseCodeIsSuccessful();
        // Translated text for staff_position.my_position is "My Position"
        $I->see('My Position', 'div.card-header');
    }

    public function mapListWithStaffPositionsParameterShowsCorrectHeader(FunctionalTester $I): void
    {
        $I->wantTo('verify that map list with staff_positions=1 shows correct header');

        $user = UserFactory::createApprovedUser();
        $larp = LarpFactory::new()->create();
        $participant = LarpParticipantFactory::new()
            ->forLarp($larp->_real())
            ->forUser($user)
            ->organizer()
            ->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->create();

        $I->amLoggedInAs($user);

        // Access map list with staff_positions parameter
        $I->amOnPage('/backoffice/larp/' . $larp->getId() . '/map/list?staff_positions=1');

        $I->seeResponseCodeIsSuccessful();
        // Translated text for staff_position.select_map is "Select Map"
        $I->see('Select Map', 'h2');
    }

    public function mapListLinkPreservesStaffPositionsParameter(FunctionalTester $I): void
    {
        $I->wantTo('verify that map links preserve staff_positions parameter');

        $user = UserFactory::createApprovedUser();
        $larp = LarpFactory::new()->create();
        $participant = LarpParticipantFactory::new()
            ->forLarp($larp->_real())
            ->forUser($user)
            ->organizer()
            ->create();
        $map = GameMapFactory::new()->forLarp($larp->_real())->create();

        $I->amLoggedInAs($user);

        // Access map list with staff_positions parameter
        $I->amOnPage('/backoffice/larp/' . $larp->getId() . '/map/list?staff_positions=1');

        $I->seeResponseCodeIsSuccessful();
        // Map name link should include staff_positions parameter
        $I->seeElement('a[href*="staff_positions=1"]');
    }
}
