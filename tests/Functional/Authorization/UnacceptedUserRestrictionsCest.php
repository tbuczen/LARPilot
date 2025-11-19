<?php

declare(strict_types=1);

namespace Tests\Functional\Authorization;

use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\FunctionalTester;

/**
 * Tests that PENDING (unaccepted) users cannot create LARPs or Locations
 *
 * Covers:
 * - PENDING users cannot access LARP creation form
 * - PENDING users cannot submit LARP creation
 * - PENDING users cannot access Location creation form
 * - PENDING users cannot submit Location creation
 * - SUSPENDED users have same restrictions
 * - BANNED users have same restrictions
 * - Appropriate error messages are displayed
 */
class UnacceptedUserRestrictionsCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
    }

    public function pendingUserCannotAccessLarpCreationForm(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING user cannot access LARP creation form');

        $pendingUser = UserFactory::createPendingUser();

        $I->amLoggedInAs($pendingUser);

        $I->amOnRoute('backoffice_larp_create');

        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function pendingUserCannotSubmitLarpCreation(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING user cannot submit LARP creation');

        $pendingUser = UserFactory::createPendingUser();

        $I->amLoggedInAs($pendingUser);

        // Try to POST directly to LARP creation endpoint
        $I->sendPOST($I->getUrl('backoffice_larp_create'), [
            'larp' => [
                'title' => 'Unauthorized LARP',
                'description' => 'This should not be created',
            ],
        ]);

        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function pendingUserCannotAccessLocationCreationForm(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING user cannot access Location creation form');

        $pendingUser = UserFactory::createPendingUser();

        $I->amLoggedInAs($pendingUser);

        $I->amOnRoute('backoffice_location_modify_global', ['location' => 'new']);

        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function pendingUserCannotSubmitLocationCreation(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING user cannot submit Location creation');

        $pendingUser = UserFactory::createPendingUser();

        $I->amLoggedInAs($pendingUser);

        // Try to POST directly to Location creation endpoint
        $I->sendPOST($I->getUrl('backoffice_location_modify_global', ['location' => 'new']), [
            'location' => [
                'name' => 'Unauthorized Location',
                'address' => '123 Test St',
                'city' => 'Test City',
                'country' => 'Test Country',
                'postalCode' => '12345',
            ],
        ]);

        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function suspendedUserCannotAccessLarpCreationForm(FunctionalTester $I): void
    {
        $I->wantTo('verify SUSPENDED user cannot access LARP creation form');

        $suspendedUser = $I->createSuspendedUser();

        $I->amLoggedInAs($suspendedUser);

        $I->amOnRoute('backoffice_larp_create');

        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function suspendedUserCannotSubmitLarpCreation(FunctionalTester $I): void
    {
        $I->wantTo('verify SUSPENDED user cannot submit LARP creation');

        $suspendedUser = $I->createSuspendedUser();

        $I->amLoggedInAs($suspendedUser);

        $I->sendPOST($I->getUrl('backoffice_larp_create'), [
            'larp' => [
                'title' => 'Unauthorized LARP',
                'description' => 'This should not be created',
            ],
        ]);

        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function suspendedUserCannotAccessLocationCreationForm(FunctionalTester $I): void
    {
        $I->wantTo('verify SUSPENDED user cannot access Location creation form');

        $suspendedUser = $I->createSuspendedUser();

        $I->amLoggedInAs($suspendedUser);

        $I->amOnRoute('backoffice_location_modify_global', ['location' => 'new']);

        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function suspendedUserCannotSubmitLocationCreation(FunctionalTester $I): void
    {
        $I->wantTo('verify SUSPENDED user cannot submit Location creation');

        $suspendedUser = $I->createSuspendedUser();

        $I->amLoggedInAs($suspendedUser);

        $I->sendPOST($I->getUrl('backoffice_location_modify_global', ['location' => 'new']), [
            'location' => [
                'name' => 'Unauthorized Location',
                'address' => '123 Test St',
            ],
        ]);

        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function bannedUserCannotAccessLarpCreationForm(FunctionalTester $I): void
    {
        $I->wantTo('verify BANNED user cannot access LARP creation form');

        $bannedUser = $I->createBannedUser();

        $I->amLoggedInAs($bannedUser);

        $I->amOnRoute('backoffice_larp_create');

        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function bannedUserCannotSubmitLarpCreation(FunctionalTester $I): void
    {
        $I->wantTo('verify BANNED user cannot submit LARP creation');

        $bannedUser = $I->createBannedUser();

        $I->amLoggedInAs($bannedUser);

        $I->sendPOST($I->getUrl('backoffice_larp_create'), [
            'larp' => [
                'title' => 'Unauthorized LARP',
                'description' => 'This should not be created',
            ],
        ]);

        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function bannedUserCannotAccessLocationCreationForm(FunctionalTester $I): void
    {
        $I->wantTo('verify BANNED user cannot access Location creation form');

        $bannedUser = $I->createBannedUser();

        $I->amLoggedInAs($bannedUser);

        $I->amOnRoute('backoffice_location_modify_global', ['location' => 'new']);

        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function bannedUserCannotSubmitLocationCreation(FunctionalTester $I): void
    {
        $I->wantTo('verify BANNED user cannot submit Location creation');

        $bannedUser = $I->createBannedUser();

        $I->amLoggedInAs($bannedUser);

        $I->sendPOST($I->getUrl('backoffice_location_modify_global', ['location' => 'new']), [
            'location' => [
                'name' => 'Unauthorized Location',
                'address' => '123 Test St',
            ],
        ]);

        $I->seeResponseCodeIsRedirection();
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function pendingUserCannotCreateLarpEntityDirectly(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING user cannot create LARP entity directly');

        $pendingUser = UserFactory::createPendingUser();

        $I->amLoggedInAs($pendingUser);

        // Verify voter denies access at service layer
        $authChecker = $I->grabService('security.authorization_checker');

        $canCreate = $authChecker->isGranted('CREATE_LARP');

        $I->assertFalse(
            $canCreate,
            'Voter should deny CREATE_LARP permission for PENDING user'
        );
    }

    public function pendingUserCannotCreateLocationEntityDirectly(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING user cannot create Location entity directly');

        $pendingUser = UserFactory::createPendingUser();

        $I->amLoggedInAs($pendingUser);

        // Verify voter denies access at service layer
        $authChecker = $I->grabService('security.authorization_checker');

        $canCreate = $authChecker->isGranted('CREATE_LOCATION');

        $I->assertFalse(
            $canCreate,
            'Voter should deny CREATE_LOCATION permission for PENDING user'
        );
    }

    public function pendingUserSeesAppropriateErrorMessageForLarpCreation(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING user sees appropriate error message for LARP creation');

        $pendingUser = UserFactory::createPendingUser();

        $I->amLoggedInAs($pendingUser);

        $I->amOnRoute('backoffice_larp_create');

        // If it's a redirect with flash message, follow and check
        $I->seeResponseCodeIsBetween(300,400);
        $I->followRedirect();
        $I->see('approved');
    }

    public function approvedUserCanAccessLarpCreationForm(FunctionalTester $I): void
    {
        $I->wantTo('verify APPROVED user can access LARP creation form');

        $approvedUser = UserFactory::createApprovedUser();

        $I->amLoggedInAs($approvedUser);

        $I->amOnRoute('backoffice_larp_create');

        $I->seeResponseCodeIsSuccessful();
    }

    public function approvedUserCanAccessLocationCreationForm(FunctionalTester $I): void
    {
        $I->wantTo('verify APPROVED user can access Location creation form');

        $approvedUser = UserFactory::createApprovedUser();

        $I->amLoggedInAs($approvedUser);

        $I->amOnRoute('backoffice_location_modify_global', ['location' => 'new']);

        $I->seeResponseCodeIsSuccessful();
    }
}
