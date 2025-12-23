<?php

declare(strict_types=1);

namespace Tests\Functional\Security;

use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\FunctionalTester;

/**
 * Tests public and player route access control
 *
 * Covers:
 * - ALL users can access public routes
 * - PENDING users can access player/account routes
 * - APPROVED users can access player/account routes
 * - Access control layers work together
 * - Status changes affect access immediately
 * - SUPER_ADMIN can access all routes
 * - Role hierarchy is respected
 */
class PublicAccessCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
    }

    public function approvedUserCanAccessPlayerRoutes(FunctionalTester $I): void
    {
        $I->wantTo('verify APPROVED users can access player routes');

        $approvedUser = UserFactory::createApprovedUser();
        $I->amLoggedInAs($approvedUser);

        $I->startFollowingRedirects();
        $I->amOnRoute('public_larp_my_larps');
        $I->seeResponseCodeIsSuccessful();
    }

    public function pendingUserCanAccessPlayerRoutes(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING users can access player routes');

        $pendingUser = UserFactory::createPendingUser();
        $I->amLoggedInAs($pendingUser);

        $I->startFollowingRedirects();
        $I->amOnRoute('public_larp_my_larps');
        $I->seeResponseCodeIsSuccessful();
    }

    public function approvedUserCanAccessAccountRoutes(FunctionalTester $I): void
    {
        $I->wantTo('verify APPROVED users can access account routes');

        $approvedUser = UserFactory::createApprovedUser();
        $I->amLoggedInAs($approvedUser);

        $I->startFollowingRedirects();
        $I->amOnRoute('account_settings');
        $I->seeResponseCodeIsSuccessful();
    }

    public function pendingUserCanAccessAccountRoutes(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING users can access account routes');

        $pendingUser = UserFactory::createPendingUser();
        $I->amLoggedInAs($pendingUser);

        $I->startFollowingRedirects();
        $I->amOnRoute('account_settings');
        $I->seeResponseCodeIsSuccessful();
    }

    public function multipleAccessControlLayersWorkTogether(FunctionalTester $I): void
    {
        $I->wantTo('verify that user status AND route access control work together');

        $pendingUser = UserFactory::createPendingUser();
        $I->amLoggedInAs($pendingUser);

        // Try backoffice (should redirect due to PENDING status)
        $I->amOnRoute('backoffice_dashboard');
        $I->seeResponseCodeIsRedirection();

        // Try LARP creation (should redirect due to voter)
        $I->amOnRoute('backoffice_larp_create');
        $I->seeResponseCodeIsRedirection();

        // Try location creation (should redirect due to voter)
        $I->amOnPage($I->getUrl('backoffice_location_modify_global', ['location' => 'new']));
        $I->seeResponseCodeIsRedirection();
    }

    public function statusChangeAffectsAccessImmediately(FunctionalTester $I): void
    {
        $I->wantTo('verify status changes affect access immediately');

        $user = UserFactory::createPendingUser();
        $I->amLoggedInAs($user);

        // Initially cannot access backoffice (redirected due to PENDING status)
        $I->amOnRoute('backoffice_dashboard');
        $I->seeResponseCodeIsRedirection();

        // Approve user
        $user->setStatus(\App\Domain\Account\Entity\Enum\UserStatus::APPROVED);
        $I->getEntityManager()->flush();

        // Clear entity manager and re-authenticate
        $I->getEntityManager()->clear();
        $I->amLoggedInAs($user);

        // Now should be able to access backoffice - need to follow redirects for this check
        $I->startFollowingRedirects();
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

        $I->startFollowingRedirects();

        foreach ($routes as $routeData) {
            $route = $routeData['route'];
            $params = $routeData['params'] ?? [];

            if (empty($params)) {
                $I->amOnRoute($route);
            } else {
                $I->amOnPage($I->getUrl($route, $params));
            }

            $statusCode = $I->grabResponse()->getStatusCode();
            $I->assertNotEquals(
                403,
                $statusCode,
                "SUPER_ADMIN should access {$route}"
            );
        }
    }

    public function roleHierarchyIsRespected(FunctionalTester $I): void
    {
        $I->wantTo('verify role hierarchy is respected');

        $superAdmin = $I->createSuperAdmin();
        $I->amLoggedInAs($superAdmin);

        // SUPER_ADMIN should have ROLE_SUPER_ADMIN
        $I->assertContains(
            'ROLE_SUPER_ADMIN',
            $superAdmin->getRoles(),
            'SUPER_ADMIN should have ROLE_SUPER_ADMIN'
        );

        // Verify role hierarchy works by checking access to admin routes
        $I->startFollowingRedirects();
        $I->amOnRoute('super_admin_users_list');
        $I->seeResponseCodeIsSuccessful();
    }

    public function publicRoutesAccessibleToAll(FunctionalTester $I): void
    {
        $I->wantTo('verify public routes are accessible without authentication');

        // Public routes should be accessible without authentication
        $publicRoutes = [
            'public_larp_list',
        ];

        $I->startFollowingRedirects();
        foreach ($publicRoutes as $route) {
            $I->amOnRoute($route);
            $I->seeResponseCodeIsSuccessful();
        }
    }

    public function pendingUserCanAccessPublicRoutes(FunctionalTester $I): void
    {
        $I->wantTo('verify PENDING users can access public routes');

        $pendingUser = UserFactory::createPendingUser();
        $I->amLoggedInAs($pendingUser);

        // Public routes should be accessible even with PENDING status
        $publicRoutes = [
            'public_larp_list',
        ];

        $I->startFollowingRedirects();
        foreach ($publicRoutes as $route) {
            $I->amOnRoute($route);
            $I->seeResponseCodeIsSuccessful();
        }
    }
}
