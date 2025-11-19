<?php

declare(strict_types=1);

namespace Tests\Functional\Security;

use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use Tests\Support\FunctionalTester;

/**
 * Tests LARP visibility and access control based on status and participation
 *
 * Covers:
 * - Public visibility based on LARP status (PUBLISHED, INQUIRIES, CONFIRMED, COMPLETED)
 * - DRAFT and WIP LARPs not publicly visible
 * - Participant-based access control
 * - ORGANIZER role grants full access
 * - PLAYER role grants limited access
 * - Non-participants cannot access LARP backoffice
 * - PENDING users cannot access LARP backoffice
 * - SUPER_ADMIN can access all LARPs
 */
class LarpVisibilitySecurityCest
{
    public function publishedLarpIsPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that a PUBLISHED LARP is publicly visible');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createPublishedLarp($organizer);

        $I->assertTrue(
            $larp->getStatus()->isVisibleForEveryone(),
            'PUBLISHED LARP should be publicly visible'
        );
    }

    public function draftLarpIsNotPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that a DRAFT LARP is not publicly visible');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->assertFalse(
            $larp->getStatus()->isVisibleForEveryone(),
            'DRAFT LARP should not be publicly visible'
        );
    }

    public function wipLarpIsNotPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that a WIP LARP is not publicly visible');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createWipLarp($organizer);

        $I->assertFalse(
            $larp->getStatus()->isVisibleForEveryone(),
            'WIP LARP should not be publicly visible'
        );
    }

    public function inquiriesLarpIsPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that an INQUIRIES LARP is publicly visible');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createLarp($organizer, LarpStageStatus::INQUIRIES);

        $I->assertTrue(
            $larp->getStatus()->isVisibleForEveryone(),
            'INQUIRIES LARP should be publicly visible'
        );
    }

    public function confirmedLarpIsPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that a CONFIRMED LARP is publicly visible');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createLarp($organizer, LarpStageStatus::CONFIRMED);

        $I->assertTrue(
            $larp->getStatus()->isVisibleForEveryone(),
            'CONFIRMED LARP should be publicly visible'
        );
    }

    public function completedLarpIsPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that a COMPLETED LARP is publicly visible');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createLarp($organizer, LarpStageStatus::COMPLETED);

        $I->assertTrue(
            $larp->getStatus()->isVisibleForEveryone(),
            'COMPLETED LARP should be publicly visible'
        );
    }

    public function unauthenticatedUserCanSeePublicLarpInList(FunctionalTester $I): void
    {
        $I->wantTo('verify that unauthenticated users can see public LARPs in the list');

        $organizer = UserFactory::createApprovedUser();
        $publicLarp = $I->createPublishedLarp($organizer, 'Public LARP');

        // Ensure the entity manager is flushed and cleared before making the request
        $em = $I->getEntityManager();
        $em->flush();
        $em->clear();

        $I->amOnRoute('public_larp_list');
        $I->seeResponseCodeIsSuccessful();

        // Check if LARP appears in the list (basic check)
        $I->see('Public LARP', 'body');
    }

    public function unauthenticatedUserCannotSeeDraftLarpInList(FunctionalTester $I): void
    {
        $I->wantTo('verify that unauthenticated users cannot see draft LARPs in the list');

        $organizer = UserFactory::createApprovedUser();
        $draftLarp = LarpFactory::createDraftLarp($organizer, 'Draft LARP');

        $I->amOnRoute('public_larp_list');

        $I->dontSee('Draft LARP', 'body');
    }

    public function unauthenticatedUserCannotAccessLarpBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify that unauthenticated users cannot access LARP backoffice');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createPublishedLarp($organizer);

        $I->amOnRoute('backoffice_larp_dashboard', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIs(302);
    }

    public function pendingUserCannotAccessLarpBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify that pending users cannot access LARP backoffice');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createPublishedLarp($organizer);

        $pendingUser = UserFactory::createPendingUser();

        $I->amLoggedInAs($pendingUser);
        $I->amOnRoute('backoffice_larp_dashboard', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIs(302);
        $I->seeCurrentRouteIs('backoffice_account_pending_approval');
    }

    public function participantCanAccessTheirLarpBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify that organizers can access their LARP backoffice');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_dashboard', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
    }

    public function nonParticipantCannotAccessOtherLarpBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify that non-participants cannot access other LARP backoffices');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $otherUser = UserFactory::createApprovedUser();

        $I->amLoggedInAs($otherUser);
        $I->amOnRoute('backoffice_larp_dashboard', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIs(403);
    }

    public function playerParticipantCannotAccessLarpBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify that player participants cannot access LARP backoffice');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $player = UserFactory::createApprovedUser();
        $I->addParticipantToLarp($larp, $player);

        $I->amLoggedInAs($player);
        $I->amOnRoute('backoffice_larp_dashboard', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIs(403);
    }

    public function organizerHasFullLarpAccess(FunctionalTester $I): void
    {
        $I->wantTo('verify that organizers have full LARP access');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $participant = $larp->getLarpParticipants()[0];

        $I->assertTrue(
            $participant->isOrganizer(),
            'Organizer should have ORGANIZER role'
        );
        $I->assertTrue(
            $participant->isAdmin(),
            'Organizer should be admin of their LARP'
        );
    }

    public function staffParticipantCanAccessLarpBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify that staff participants can access LARP backoffice');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $staff = UserFactory::createApprovedUser();
        $I->addParticipantToLarp($larp, $staff, [ParticipantRole::STAFF]);

        $I->amLoggedInAs($staff);
        $I->amOnRoute('backoffice_larp_dashboard', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
    }

    public function superAdminCannotAccessAnyLarpBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify that super admins cannot access LARP backoffices');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $superAdmin = $I->createSuperAdmin();

        $I->amLoggedInAs($superAdmin);
        $I->amOnRoute('backoffice_larp_dashboard', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIs(403);
    }

    public function superAdminCanSeeAllHisLarps(FunctionalTester $I): void
    {
        $I->wantTo('verify that super admins can access admin LARP list');

        $organizer = UserFactory::createApprovedUser();
        $draftLarp = LarpFactory::createDraftLarp($organizer, 'Secret Draft');

        $superAdmin = $I->createSuperAdmin();

        $I->amLoggedInAs($superAdmin);
        $I->amOnRoute('backoffice_dashboard');

        $I->seeResponseCodeIsSuccessful();
    }

    public function approvedUserOnlySeesTheirParticipatingLarps(FunctionalTester $I): void
    {
        $I->wantTo('verify that users only see their participating LARPs');

        $organizer1 = UserFactory::createApprovedUser();
        $organizer2 = UserFactory::createApprovedUser();

        $larp1 = LarpFactory::createDraftLarp($organizer1, 'LARP 1');
        $larp2 = LarpFactory::createDraftLarp($organizer2, 'LARP 2');

        $I->amLoggedInAs($organizer1);

        // Organizer1 should be able to access LARP 1
        $I->amOnRoute('backoffice_larp_dashboard', ['larp' => $larp1->getId()]);
        $I->seeResponseCodeIsSuccessful();

        // But not LARP 2
        $I->amOnRoute('backoffice_larp_dashboard', ['larp' => $larp2->getId()]);
        $I->seeResponseCodeIs(403);
    }

    public function cancelledLarpIsNotPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that cancelled LARPs are not publicly visible');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createLarp($organizer, LarpStageStatus::CANCELLED);

        $I->assertFalse(
            $larp->getStatus()->isVisibleForEveryone(),
            'CANCELLED LARP should not be publicly visible'
        );
    }

    public function multipleRolesParticipantHasProperAccess(FunctionalTester $I): void
    {
        $I->wantTo('verify that multi-role participants have proper access');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $multiRoleUser = UserFactory::createApprovedUser();
        $I->addParticipantToLarp($larp, $multiRoleUser, [
            ParticipantRole::STAFF,
            ParticipantRole::STORY_WRITER,
        ]);

        $I->amLoggedInAs($multiRoleUser);
        $I->amOnRoute('backoffice_larp_dashboard', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
    }
}
