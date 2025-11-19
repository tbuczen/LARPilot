<?php

declare(strict_types=1);

namespace Tests\Functional\Security;

use Tests\Support\FunctionalTester;

/**
 * Tests backoffice access control based on user status and roles (Codeception)
 *
 * Covers:
 * - Unauthenticated users redirected to login
 * - PENDING users cannot access /backoffice
 * - APPROVED users can access /backoffice
 * - SUSPENDED users cannot access /backoffice
 * - BANNED users cannot access /backoffice
 * - SUPER_ADMIN can access /super-admin routes
 * - Regular users cannot access /super-admin routes
 */
class BackofficeAccessCest
{
    public function unauthenticatedUserRedirectedFromBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify unauthenticated users are redirected from backoffice');

        $I->amOnRoute('backoffice_dashboard');
        $I->seeResponseCodeIs(302);
    }

    public function pendingUserCannotAccessBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING users cannot access backoffice');

        $pendingUser = UserFactory::createPendingUser();
        $I->amLoggedInAs($pendingUser);

        $I->amOnRoute('backoffice_dashboard');
        $I->seeResponseCodeIs(302);
    }

    public function approvedUserCanAccessBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify APPROVED users can access backoffice');

        $approvedUser = UserFactory::createApprovedUser();
        $I->amLoggedInAs($approvedUser);

        $I->amOnRoute('backoffice_dashboard');
        $I->seeResponseCodeIsSuccessful();
    }

    public function suspendedUserCannotAccessBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify SUSPENDED users cannot access backoffice');

        $suspendedUser = $I->createSuspendedUser();
        $I->amLoggedInAs($suspendedUser);

        $I->amOnRoute('backoffice_dashboard');
        $I->seeResponseCodeIs(302);
    }

    public function bannedUserCannotAccessBackoffice(FunctionalTester $I): void
    {
        $I->wantTo('verify BANNED users cannot access backoffice');

        $bannedUser = $I->createBannedUser();
        $I->amLoggedInAs($bannedUser);

        $I->amOnRoute('backoffice_dashboard');
        $I->seeResponseCodeIs(302);
    }

    public function superAdminCanAccessSuperAdminRoutes(FunctionalTester $I): void
    {
        $I->wantTo('verify SUPER_ADMIN can access super-admin routes');

        $superAdmin = $I->createSuperAdmin();
        $I->amLoggedInAs($superAdmin);

        $I->amOnRoute('super_admin_users_list');
        $I->seeResponseCodeIsSuccessful();
    }

    public function regularApprovedUserCannotAccessSuperAdminRoutes(FunctionalTester $I): void
    {
        $I->wantTo('verify regular APPROVED users cannot access super-admin routes');

        $regularUser = UserFactory::createApprovedUser();
        $I->amLoggedInAs($regularUser);

        $I->amOnRoute('super_admin_users_list');
        $I->seeResponseCodeIs(403);
    }

    public function pendingUserCannotAccessSuperAdminRoutes(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING users cannot access super-admin routes');

        $pendingUser = UserFactory::createPendingUser();
        $I->amLoggedInAs($pendingUser);

        $I->amOnRoute('super_admin_users_list');
        $I->seeResponseCodeIs(403);
    }

    public function unauthenticatedUserRedirectedFromSuperAdminRoutes(FunctionalTester $I): void
    {
        $I->wantTo('verify unauthenticated users are redirected from super-admin routes');

        $I->amOnRoute('super_admin_users_list');
        $I->seeResponseCodeIs(302);
    }

    public function multipleAccessControlLayersWorkTogether(FunctionalTester $I): void
    {
        $I->wantTo('verify that user status AND route access control work together');

        $pendingUser = UserFactory::createPendingUser();
        $I->amLoggedInAs($pendingUser);

        // Try backoffice (should redirect due to PENDING status)
        $I->amOnRoute('backoffice_dashboard');
        $I->seeResponseCodeIs(302);

        // Try LARP creation (should redirect due to voter)
        $I->amOnRoute('backoffice_larp_create');
        $I->seeResponseCodeIs(302);

        // Try location creation (should redirect due to voter)
        $I->amOnPage($I->getUrl('backoffice_location_modify_global', ['location' => 'new']));
        $I->seeResponseCodeIs(302);
    }

    public function statusChangeAffectsAccessImmediately(FunctionalTester $I): void
    {
        $I->wantTo('verify status changes affect access immediately');

        $user = UserFactory::createPendingUser();
        $I->amLoggedInAs($user);

        // Initially cannot access backoffice
        $I->amOnRoute('backoffice_dashboard');
        $I->seeResponseCodeIs(302);

        // Approve user
        $user->setStatus(\App\Domain\Account\Entity\Enum\UserStatus::APPROVED);
        $I->getEntityManager()->flush();

        // Clear entity manager and re-authenticate
        $I->getEntityManager()->clear();
        $I->amLoggedInAs($user);

        // Now should be able to access backoffice
        $I->amOnRoute('backoffice_dashboard');
        $I->seeResponseCodeIsSuccessful();
    }

    public function superAdminCanAccessAllRoutes(FunctionalTester $I): void
    {
        $I->wantTo('verify SUPER_ADMIN can access all routes');

        $superAdmin = $I->createSuperAdmin();
        $I->amLoggedInAs($superAdmin);

        // Test multiple routes
        $routes = [
            ['route' => 'backoffice_dashboard'],
            ['route' => 'super_admin_users_list'],
            ['route' => 'backoffice_larp_create'],
            ['route' => 'backoffice_location_modify_global', 'params' => ['location' => 'new']],
        ];

        foreach ($routes as $routeData) {
            $route = $routeData['route'];
            $params = $routeData['params'] ?? [];

            if (empty($params)) {
                $I->amOnRoute($route);
            } else {
                $I->amOnPage($I->getUrl($route, $params));
            }

            $statusCode = $I->grabResponse()->getStatusCode();
            $I->assertNotEquals(403, $statusCode, "SUPER_ADMIN should access {$route}");
        }
    }

    public function roleHierarchyIsRespected(FunctionalTester $I): void
    {
        $I->wantTo('verify role hierarchy is respected');

        $superAdmin = $I->createSuperAdmin();
        $I->amLoggedInAs($superAdmin);

        // SUPER_ADMIN should have ROLE_SUPER_ADMIN
        $I->assertContains('ROLE_SUPER_ADMIN', $superAdmin->getRoles());

        // Verify role hierarchy works
        $I->amOnRoute('super_admin_users_list');
        $I->seeResponseCodeIsSuccessful();
    }

    public function publicRoutesAccessibleToAll(FunctionalTester $I): void
    {
        $I->wantTo('verify public routes are accessible without authentication');

        $publicRoutes = ['public_larp_list', 'public_larp_list'];

        foreach ($publicRoutes as $route) {
            $I->amOnRoute($route);
            $I->seeResponseCodeIsSuccessful();
        }
    }

    public function unauthenticatedUserRedirectedFromBackofficeRoute(FunctionalTester $I): void
    {
        $I->wantTo('verify unauthenticated users are redirected from backoffice LARP list');

        $I->amOnRoute('backoffice_larp_list');
        $I->seeResponseCodeIs(302);
    }

    public function pendingUserCanAccessPublicRoutes(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING users can access public routes');

        $pendingUser = UserFactory::createPendingUser();
        $I->amLoggedInAs($pendingUser);

        $publicRoutes = ['public_larp_list', 'public_larp_list'];

        foreach ($publicRoutes as $route) {
            $I->amOnRoute($route);
            $I->seeResponseCodeIsSuccessful();
        }
    }
}
