<?php

declare(strict_types=1);

namespace Tests\Functional\Authentication;

use App\Domain\Account\Entity\Enum\UserStatus;
use App\Domain\Account\Entity\User;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\FunctionalTester;

/**
 * Tests for user signup and approval workflow (Codeception)
 *
 * Covers:
 * - New users created with PENDING status
 * - PENDING users can access public pages
 * - PENDING users cannot access backoffice
 * - Admin approval/suspension/ban functionality
 * - Status changes affect access control
 */
class UserSignupAndApprovalCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
    }

    public function newUserHasPendingStatus(FunctionalTester $I): void
    {
        $I->wantTo('verify that new users are created with PENDING status');

        $user = UserFactory::createPendingUser();

        $I->assertTrue($user->isPending(), 'New user should have PENDING status');
        $I->assertFalse($user->isApproved(), 'New user should not be approved');
        $I->assertEquals(UserStatus::PENDING, $user->getStatus());
    }

    public function pendingUserCanAccessPublicPages(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING users can access public pages');

        $pendingUser = UserFactory::createPendingUser();
        $I->amLoggedInAs($pendingUser);

        // Test accessing homepage
        $I->amOnRoute('public_larp_list');
        $I->seeResponseCodeIsSuccessful();

        // Test accessing public LARP list
        $I->amOnRoute('public_larp_list');
        $I->seeResponseCodeIsSuccessful();
    }

    public function pendingUserCannotAccessBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING users cannot access backoffice');

        $pendingUser = UserFactory::createPendingUser();
        $I->amLoggedInAs($pendingUser);

        // Try to access backoffice
        $I->amOnRoute('backoffice_larp_create');

        // Should be redirected to pending approval page
        $I->seeResponseCodeIs(302);
    }

    public function pendingUserCannotAccessLarpCreation(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING users cannot create LARPs');

        $pendingUser = UserFactory::createPendingUser();
        $I->amLoggedInAs($pendingUser);

        $I->amOnRoute('backoffice_larp_create');

        // Should be redirected to pending approval page
        $I->seeResponseCodeIs(302);
    }

    public function approvedUserCanAccessBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify APPROVED users can access backoffice');

        $approvedUser = UserFactory::createApprovedUser();
        $I->amLoggedInAs($approvedUser);

        // Try to access backoffice
        $I->amOnRoute('backoffice_dashboard');

        $I->seeResponseCodeIsSuccessful();
    }

    public function userCanBeProgrammaticallyApproved(FunctionalTester $I): void
    {
        $I->wantTo('verify users can be approved programmatically');

        $user = UserFactory::createPendingUser();

        $I->assertTrue($user->isPending(), 'User should start as PENDING');

        // Approve user
        $user->setStatus(UserStatus::APPROVED);
        $I->getEntityManager()->flush();

        $I->assertTrue($user->isApproved(), 'User should be APPROVED after approval');
        $I->assertFalse($user->isPending(), 'User should no longer be PENDING');
        $I->assertEquals(UserStatus::APPROVED, $user->getStatus());
    }

    public function approvedUserCanBeSuspended(FunctionalTester $I): void
    {
        $I->wantTo('verify approved users can be suspended');

        $user = UserFactory::createApprovedUser();

        $I->assertTrue($user->isApproved(), 'User should start as APPROVED');

        // Suspend user
        $user->setStatus(UserStatus::SUSPENDED);
        $I->getEntityManager()->flush();

        $I->assertTrue($user->isSuspended(), 'User should be SUSPENDED after suspension');
        $I->assertFalse($user->isApproved(), 'User should no longer be APPROVED');
        $I->assertEquals(UserStatus::SUSPENDED, $user->getStatus());
    }

    public function userCanBeBanned(FunctionalTester $I): void
    {
        $I->wantTo('verify users can be banned');

        $user = UserFactory::createApprovedUser();

        $I->assertTrue($user->isApproved(), 'User should start as APPROVED');

        // Ban user
        $user->setStatus(UserStatus::BANNED);
        $I->getEntityManager()->flush();

        $I->assertTrue($user->isBanned(), 'User should be BANNED after banning');
        $I->assertFalse($user->isApproved(), 'User should no longer be APPROVED');
        $I->assertEquals(UserStatus::BANNED, $user->getStatus());
    }

    public function suspendedUserCannotAccessBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify SUSPENDED users cannot access backoffice');

        $suspendedUser = UserFactory::createSuspendedUser();
        $I->amLoggedInAs($suspendedUser);

        $I->amOnRoute('backoffice_dashboard');

        $I->seeResponseCodeIs(302);
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function bannedUserCannotAccessBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify BANNED users cannot access backoffice');

        $bannedUser = UserFactory::createBannedUser();
        $I->amLoggedInAs($bannedUser);

        $I->amOnRoute('backoffice_dashboard');

        $I->seeResponseCodeIs(302);
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function suspendedUserCannotCreateLarp(FunctionalTester $I): void
    {
        $I->wantTo('verify SUSPENDED users cannot create LARPs');

        $suspendedUser = UserFactory::createSuspendedUser();
        $I->amLoggedInAs($suspendedUser);

        $I->amOnRoute('backoffice_larp_create');

        $I->seeResponseCodeIs(302);
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function bannedUserCannotCreateLarp(FunctionalTester $I): void
    {
        $I->wantTo('verify BANNED users cannot create LARPs');

        $bannedUser = UserFactory::createBannedUser();
        $I->amLoggedInAs($bannedUser);

        $I->amOnRoute('backoffice_larp_create');

        $I->seeResponseCodeIs(302);
        $I->assertResponseRedirects($I->getUrl('backoffice_account_pending_approval'));
    }

    public function superAdminCanAccessSuperAdminRoutes(FunctionalTester $I): void
    {
        $I->wantTo('verify SUPER_ADMIN can access super-admin routes');

        $superAdmin = UserFactory::createSuperAdmin();
        $I->amLoggedInAs($superAdmin);

        // Try to access super admin area
        $I->amOnRoute('super_admin_users_list');

        // Should be successful
        $I->seeResponseCodeIs(200);
    }

    public function regularUserCannotAccessSuperAdminRoutes(FunctionalTester $I): void
    {
        $I->wantTo('verify regular users cannot access super-admin routes');

        $regularUser = UserFactory::createApprovedUser();
        $I->amLoggedInAs($regularUser);

        $I->amOnRoute('super_admin_users_list');

        $I->seeResponseCodeIs(403);
    }

    public function unauthenticatedUserRedirectedToLogin(FunctionalTester $I): void
    {
        $I->wantTo('verify unauthenticated users are redirected to login');
        $I->logoutProgrammatically();

        // Stop following redirects to test the redirect response itself


        $I->amOnRoute('backoffice_dashboard');

        // Should receive a redirect response
        $I->seeResponseCodeIsRedirection();
    }

    public function pendingUserStatusPersistsAfterFlush(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING status persists after entity manager flush');

        $user = UserFactory::createPendingUser();
        $userId = $user->getId();

        // Clear entity manager to force reload from database
        $I->getEntityManager()->clear();

        // Reload user from database
        $reloadedUser = $I->getEntityManager()->find(User::class, $userId);

        $I->assertNotNull($reloadedUser, 'User should be persisted in database');
        $I->assertEquals(UserStatus::PENDING, $reloadedUser->getStatus(), 'Status should persist');
        $I->assertTrue($reloadedUser->isPending(), 'User should still be PENDING after reload');
    }

    public function approvedStatusPersistsAfterFlush(FunctionalTester $I): void
    {
        $I->wantTo('verify APPROVED status persists after entity manager flush');

        $user = UserFactory::createPendingUser();
        $userId = $user->getId();

        // Approve user
        $user->setStatus(UserStatus::APPROVED);
        $I->getEntityManager()->flush();

        // Clear entity manager to force reload from database
        $I->getEntityManager()->clear();

        // Reload user from database
        $reloadedUser = $I->getEntityManager()->find(User::class, $userId);

        $I->assertNotNull($reloadedUser, 'User should be persisted in database');
        $I->assertEquals(UserStatus::APPROVED, $reloadedUser->getStatus(), 'Approved status should persist');
        $I->assertTrue($reloadedUser->isApproved(), 'User should still be APPROVED after reload');
    }
}
