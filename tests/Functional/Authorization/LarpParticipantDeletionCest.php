<?php

declare(strict_types=1);

namespace Tests\Functional\Authorization;

use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\FunctionalTester;

/**
 * Tests LARP participant deletion authorization and business logic
 *
 * Covers:
 * - Only participants with ROLE_ORGANIZER can delete participants
 * - Last ROLE_ORGANIZER cannot delete themselves
 * - Multiple ROLE_ORGANIZERs can delete themselves
 * - NPCs/Players/Staff/GMs cannot delete anyone (only ROLE_ORGANIZER can)
 * - ROLE_ORGANIZER can delete other participants
 * - Non-participants have no delete permissions
 */
class LarpParticipantDeletionCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
    }

    public function nonParticipantCannotDeleteParticipant(FunctionalTester $I): void
    {
        $I->wantTo('verify non-participant cannot delete participant');

        // Arrange: Create LARP with organizer and a player
        $organizer = UserFactory::new()->approved()->create();
        $player = UserFactory::new()->approved()->create();
        $nonParticipant = UserFactory::new()->approved()->create();

        $larp = LarpFactory::createDraftLarp($organizer);

        $playerParticipant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($player)
            ->player()
            ->create();

        // Act: Non-participant tries to delete player
        $I->amLoggedInAs($nonParticipant->_real());
        $I->amOnRoute('backoffice_larp_participant_delete', [
            'larp' => $larp->getId(),
            'participant' => $playerParticipant->getId(),
        ]);

        // Assert: Access denied (403) - voter blocks non-participants
        $I->seeResponseCodeIs(403);
    }

    public function playerCannotDeleteAnyParticipant(FunctionalTester $I): void
    {
        $I->wantTo('verify player cannot delete any participant');

        // Arrange: Create LARP with organizer and two players
        $organizer = UserFactory::new()->approved()->create();
        $player1 = UserFactory::new()->approved()->create();
        $player2 = UserFactory::new()->approved()->create();

        $larp = LarpFactory::createDraftLarp($organizer);

        $player1Participant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($player1)
            ->player()
            ->create();

        $player2Participant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($player2)
            ->player()
            ->create();

        // Act: Player1 tries to delete Player2
        $I->amLoggedInAs($player1->_real());
        $I->amOnRoute('backoffice_larp_participant_delete', [
            'larp' => $larp->getId(),
            'participant' => $player2Participant->getId(),
        ]);

        // Assert: Access denied - voter blocks players from deleting
        $I->seeResponseCodeIs(403);
    }

    public function npcCannotDeleteOrganizer(FunctionalTester $I): void
    {
        $I->wantTo('verify NPC cannot delete organizer');

        // Arrange: Create LARP with organizer and NPC
        $organizer = UserFactory::new()->approved()->create();
        $npc = UserFactory::new()->approved()->create();

        $larp = LarpFactory::createDraftLarp($organizer);

        $npcParticipant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($npc)
            ->npcShort()
            ->create();

        // Get organizer's participant record
        $organizerParticipant = LarpParticipantFactory::repository()
            ->findOneBy([
                'larp' => $larp->_real(),
                'user' => $organizer->_real()
            ]);

        // Act: NPC tries to delete organizer
        $I->amLoggedInAs($npc->_real());
        $I->amOnRoute('backoffice_larp_participant_delete', [
            'larp' => $larp->getId(),
            'participant' => $organizerParticipant->getId(),
        ]);

        // Assert: Access denied - voter blocks NPCs
        $I->seeResponseCodeIs(403);
    }

    public function storyWriterCannotDeleteParticipants(FunctionalTester $I): void
    {
        $I->wantTo('verify story writer cannot delete participants');

        // Arrange: Create LARP with organizer and story writer
        $organizer = UserFactory::new()->approved()->create();
        $storyWriter = UserFactory::new()->approved()->create();
        $player = UserFactory::new()->approved()->create();

        $larp = LarpFactory::createDraftLarp($organizer);

        $storyWriterParticipant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($storyWriter)
            ->storyWriter()
            ->create();

        $playerParticipant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($player)
            ->player()
            ->create();

        // Act: Story writer tries to delete player
        $I->amLoggedInAs($storyWriter->_real());
        $I->amOnRoute('backoffice_larp_participant_delete', [
            'larp' => $larp->getId(),
            'participant' => $playerParticipant->getId(),
        ]);

        // Assert: Access denied - only organizers can delete
        $I->seeResponseCodeIs(403);
    }

    public function lastOrganizerCannotDeleteThemselves(FunctionalTester $I): void
    {
        $I->wantTo('verify last organizer cannot delete themselves');

        // Arrange: Create LARP with single organizer
        $organizer = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($organizer);

        // Get organizer's participant record
        $organizerParticipant = LarpParticipantFactory::repository()
            ->findOneBy([
                'larp' => $larp->_real(),
                'user' => $organizer->_real()
            ]);

        // Act: Last organizer tries to delete themselves
        $I->amLoggedInAs($organizer->_real());
        $I->amOnRoute('backoffice_larp_participant_delete', [
            'larp' => $larp->getId(),
            'participant' => $organizerParticipant->getId(),
        ]);

        // Assert: Redirect with error message (business logic blocks this)
        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_larp_participant_list', ['larp' => $larp->getId()]));

        // Follow redirect and check flash message
        $I->followRedirect();
        $I->see('cannot remove yourself', '.alert-danger');

        // Verify organizer still exists
        LarpParticipantFactory::assert()->exists($organizerParticipant);
    }

    public function organizerCanDeleteThemselvesWhenMultipleOrganizersExist(FunctionalTester $I): void
    {
        $I->wantTo('verify organizer can delete themselves when multiple organizers exist');

        // Arrange: Create LARP with two organizers
        $organizer1 = UserFactory::new()->approved()->create();
        $organizer2 = UserFactory::new()->approved()->create();

        $larp = LarpFactory::createDraftLarp($organizer1);

        $organizer2Participant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($organizer2)
            ->organizer()
            ->create();

        // Get organizer1's participant record
        $organizer1Participant = LarpParticipantFactory::repository()
            ->findOneBy([
                'larp' => $larp->_real(),
                'user' => $organizer1->_real()
            ]);

        // Act: First organizer deletes themselves (second organizer still exists)
        $I->amLoggedInAs($organizer1->_real());
        $I->amOnRoute('backoffice_larp_participant_delete', [
            'larp' => $larp->getId(),
            'participant' => $organizer1Participant->getId(),
        ]);

        // Assert: Successful deletion with redirect
        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_larp_participant_list', ['larp' => $larp->getId()]));

        // Follow redirect and check success message
        $I->followRedirect();
        $I->seeElement('.alert-success');

        // Verify organizer1 was deleted
        LarpParticipantFactory::assert()->notExists(['id' => $organizer1Participant->getId()]);

        // Verify organizer2 still exists
        LarpParticipantFactory::assert()->exists($organizer2Participant);
    }

    public function organizerCanDeletePlayer(FunctionalTester $I): void
    {
        $I->wantTo('verify organizer can delete player');

        // Arrange: Create LARP with organizer and player
        $organizer = UserFactory::new()->approved()->create();
        $player = UserFactory::new()->approved()->create();

        $larp = LarpFactory::createDraftLarp($organizer);

        $playerParticipant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($player)
            ->player()
            ->create();

        // Act: Organizer deletes player
        $I->amLoggedInAs($organizer->_real());
        $I->amOnRoute('backoffice_larp_participant_delete', [
            'larp' => $larp->getId(),
            'participant' => $playerParticipant->getId(),
        ]);

        // Assert: Successful deletion
        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_larp_participant_list', ['larp' => $larp->getId()]));

        $I->followRedirect();
        $I->seeElement('.alert-success');

        // Verify player was deleted
        LarpParticipantFactory::assert()->notExists(['id' => $playerParticipant->getId()]);
    }

    public function organizerCanDeleteNpc(FunctionalTester $I): void
    {
        $I->wantTo('verify organizer can delete NPC');

        // Arrange: Create LARP with organizer and NPC
        $organizer = UserFactory::new()->approved()->create();
        $npc = UserFactory::new()->approved()->create();

        $larp = LarpFactory::createDraftLarp($organizer);

        $npcParticipant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($npc)
            ->npcLong()
            ->create();

        // Act: Organizer deletes NPC
        $I->amLoggedInAs($organizer->_real());
        $I->amOnRoute('backoffice_larp_participant_delete', [
            'larp' => $larp->getId(),
            'participant' => $npcParticipant->getId(),
        ]);

        // Assert: Successful deletion
        $I->seeResponseCodeIsRedirection();
        $I->followRedirect();
        $I->seeElement('.alert-success');

        // Verify NPC was deleted
        LarpParticipantFactory::assert()->notExists(['id' => $npcParticipant->getId()]);
    }

    public function organizerCanDeleteStoryWriter(FunctionalTester $I): void
    {
        $I->wantTo('verify organizer can delete story writer');

        // Arrange: Create LARP with organizer and story writer
        $organizer = UserFactory::new()->approved()->create();
        $writer = UserFactory::new()->approved()->create();

        $larp = LarpFactory::createDraftLarp($organizer);

        $writerParticipant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($writer)
            ->storyWriter()
            ->create();

        // Act: Organizer deletes story writer
        $I->amLoggedInAs($organizer->_real());
        $I->amOnRoute('backoffice_larp_participant_delete', [
            'larp' => $larp->getId(),
            'participant' => $writerParticipant->getId(),
        ]);

        // Assert: Successful deletion
        $I->seeResponseCodeIsRedirection();
        $I->followRedirect();
        $I->seeElement('.alert-success');

        // Verify writer was deleted
        LarpParticipantFactory::assert()->notExists(['id' => $writerParticipant->getId()]);
    }

    public function organizerCanDeleteAnotherOrganizerWhenMultipleExist(FunctionalTester $I): void
    {
        $I->wantTo('verify organizer can delete another organizer when multiple exist');

        // Arrange: Create LARP with three organizers
        $organizer1 = UserFactory::new()->approved()->create();
        $organizer2 = UserFactory::new()->approved()->create();
        $organizer3 = UserFactory::new()->approved()->create();

        $larp = LarpFactory::createDraftLarp($organizer1);

        $organizer2Participant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($organizer2)
            ->organizer()
            ->create();

        $organizer3Participant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($organizer3)
            ->organizer()
            ->create();

        // Act: Organizer1 deletes Organizer2 (Organizer3 still exists)
        $I->amLoggedInAs($organizer1->_real());
        $I->amOnRoute('backoffice_larp_participant_delete', [
            'larp' => $larp->getId(),
            'participant' => $organizer2Participant->getId(),
        ]);

        // Assert: Successful deletion
        $I->seeResponseCodeIsRedirection();
        $I->followRedirect();
        $I->seeElement('.alert-success');

        // Verify organizer2 was deleted
        LarpParticipantFactory::assert()->notExists(['id' => $organizer2Participant->getId()]);

        // Verify organizer3 still exists
        LarpParticipantFactory::assert()->exists($organizer3Participant);
    }

    public function staffCannotDeleteParticipants(FunctionalTester $I): void
    {
        $I->wantTo('verify STAFF cannot delete participants');

        // Arrange: STAFF is NOT ROLE_ORGANIZER, so they cannot delete
        $organizer = UserFactory::new()->approved()->create();
        $staff = UserFactory::new()->approved()->create();
        $player = UserFactory::new()->approved()->create();

        $larp = LarpFactory::createDraftLarp($organizer);

        // Add staff as STAFF role (not ROLE_ORGANIZER)
        LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($staff)
            ->staff()
            ->create();

        $playerParticipant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($player)
            ->player()
            ->create();

        // Act: Staff tries to delete player
        $I->amLoggedInAs($staff->_real());
        $I->amOnRoute('backoffice_larp_participant_delete', [
            'larp' => $larp->getId(),
            'participant' => $playerParticipant->getId(),
        ]);

        // Assert: Access denied (STAFF is not ROLE_ORGANIZER)
        $I->seeResponseCodeIs(403);
    }

    public function gameMasterCannotDeleteParticipants(FunctionalTester $I): void
    {
        $I->wantTo('verify GAME_MASTER cannot delete participants');

        // Arrange: GAME_MASTER is NOT ROLE_ORGANIZER, so they cannot delete
        $organizer = UserFactory::new()->approved()->create();
        $gameMaster = UserFactory::new()->approved()->create();
        $player = UserFactory::new()->approved()->create();

        $larp = LarpFactory::createDraftLarp($organizer);

        // Add GM as GAME_MASTER role (not ROLE_ORGANIZER)
        LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($gameMaster)
            ->gameMaster()
            ->create();

        $playerParticipant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($player)
            ->player()
            ->create();

        // Act: Game Master tries to delete player
        $I->amLoggedInAs($gameMaster->_real());
        $I->amOnRoute('backoffice_larp_participant_delete', [
            'larp' => $larp->getId(),
            'participant' => $playerParticipant->getId(),
        ]);

        // Assert: Access denied (GAME_MASTER is not ROLE_ORGANIZER)
        $I->seeResponseCodeIs(403);
    }

    public function unauthenticatedUserCannotDeleteParticipant(FunctionalTester $I): void
    {
        $I->wantTo('verify unauthenticated user cannot delete participant');

        // Arrange: Create LARP with participant
        $organizer = UserFactory::new()->approved()->create();
        $player = UserFactory::new()->approved()->create();

        $larp = LarpFactory::createDraftLarp($organizer);

        $playerParticipant = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($player)
            ->player()
            ->create();

        // Act: Unauthenticated request to delete participant
        $I->amOnRoute('backoffice_larp_participant_delete', [
            'larp' => $larp->getId(),
            'participant' => $playerParticipant->getId(),
        ]);

        // Assert: Redirect to login
        $I->seeResponseCodeIsRedirection();
    }
}
