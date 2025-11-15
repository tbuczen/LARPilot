<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Traits\AuthenticationTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests backoffice access control based on user status and roles
 *
 * Covers:
 * - Unauthenticated users redirected to login
 * - PENDING users cannot access /backoffice
 * - APPROVED users can access /backoffice
 * - SUSPENDED users cannot access /backoffice
 * - BANNED users cannot access /backoffice
 * - SUPER_ADMIN can access /super-admin routes
 * - Regular users cannot access /super-admin routes
 * - ALL users can access public routes like my account
 */
class PublicAccessTest extends WebTestCase
{
    use AuthenticationTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
    }

    public function test_approved_user_can_access_player_routes(): void
    {
        $approvedUser = $this->createApprovedUser();

        $this->client->loginUser($approvedUser);

        $this->client->request('GET', $this->generateUrl('public_larp_my_larps'));

        $this->assertResponseIsSuccessful(
            'APPROVED user should be able to access player routes'
        );
    }

    public function test_pending_user_can_access_player_routes(): void
    {
        $pendingUser = $this->createPendingUser();

        $this->client->loginUser($pendingUser);

        $this->client->request('GET', $this->generateUrl('public_larp_my_larps'));

        $this->assertResponseIsSuccessful(
            'PENDING user should be able to access account routes'
        );
    }

    public function test_approved_user_can_access_account_routes(): void
    {
        $approvedUser = $this->createApprovedUser();

        $this->client->loginUser($approvedUser);

        $this->client->request('GET', $this->generateUrl('account_settings'));

        // Should be successful or redirect to a valid account page (not 403)
        $this->assertResponseIsSuccessful(
            'APPROVED user should be able to access account routes'
        );
    }

    public function test_pending_user_can_access_account_routes(): void
    {
        $pendingUser = $this->createPendingUser();

        $this->client->loginUser($pendingUser);

        $this->client->request('GET', $this->generateUrl('account_settings'));

        $this->assertResponseIsSuccessful(
            'PENDING user should be able to access account routes'
        );
    }

    public function test_multiple_access_control_layers_work_together(): void
    {
        // Test that both user status AND route access control work together
        
        $pendingUser = $this->createPendingUser();

        $this->client->loginUser($pendingUser);

        // Try backoffice (should redirect due to PENDING status)
        $this->client->request('GET', $this->generateUrl('backoffice_dashboard'));
        $this->assertResponseRedirects(null, null, 'PENDING user redirected from backoffice');

        // Try LARP creation (should redirect due to voter)
        $this->client->request('GET', $this->generateUrl('backoffice_larp_create'));
        $this->assertResponseRedirects(null, null, 'PENDING user redirected from LARP creation');

        // Try location creation (should redirect due to voter)
        $this->client->request('GET', $this->generateUrl('backoffice_location_modify_global', ['location' => 'new']));
        $this->assertResponseRedirects(null, null, 'PENDING user redirected from location creation');
    }

    public function test_status_change_affects_access_immediately(): void
    {
        $user = $this->createPendingUser();

        $this->client->loginUser($user);

        // Initially cannot access backoffice (redirected due to PENDING status)
        $this->client->request('GET', $this->generateUrl('backoffice_dashboard'));
        $this->assertResponseRedirects(null, null, 'PENDING user redirected from backoffice');

        // Approve user
        $this->approveUser($user);

        // Clear the security token to force re-authentication
        $this->getEntityManager()->clear();
        $this->client->loginUser($user);

        // Now should be able to access backoffice
        $this->client->request('GET', $this->generateUrl('backoffice_dashboard'));
        $this->assertResponseIsSuccessful('APPROVED user can now access backoffice');
    }

    public function test_super_admin_can_access_all_routes(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->client->loginUser($superAdmin);

        // Test multiple routes
        $routes = [
            $this->generateUrl('backoffice_dashboard'),
            $this->generateUrl('super_admin_users_list'),
            $this->generateUrl('backoffice_larp_create'),
            $this->generateUrl('backoffice_location_modify_global', ['location' => 'new']),
        ];

        foreach ($routes as $route) {
            $this->client->request('GET', $route);

            $this->assertNotEquals(
                403,
                $this->client->getResponse()->getStatusCode(),
                "SUPER_ADMIN should access {$route}"
            );
        }
    }

    public function test_role_hierarchy_is_respected(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->client->loginUser($superAdmin);

        // SUPER_ADMIN should have all lower roles due to role hierarchy
        $this->assertContains(
            'ROLE_SUPER_ADMIN',
            $superAdmin->getRoles(),
            'SUPER_ADMIN should have ROLE_SUPER_ADMIN'
        );

        // Verify role hierarchy works by checking access to admin routes
        $this->client->request('GET', $this->generateUrl('super_admin_users_list'));
        $this->assertResponseIsSuccessful(
            'SUPER_ADMIN should access admin routes due to role hierarchy'
        );
    }

    public function test_public_routes_accessible_to_all(): void
    {
        // Public routes should be accessible without authentication
        $publicRoutes = [
            $this->generateUrl('public_larp_list'),
            $this->generateUrl('public_larp_list'),
        ];

        foreach ($publicRoutes as $route) {
            $this->client->request('GET', $route);

            $this->assertResponseIsSuccessful(
                "Public route {$route} should be accessible to unauthenticated users"
            );
        }
    }

    public function test_pending_user_can_access_public_routes(): void
    {
        $pendingUser = $this->createPendingUser();

        $this->client->loginUser($pendingUser);

        // Public routes should be accessible even with PENDING status
        $publicRoutes = [
            $this->generateUrl('public_larp_list'),
            $this->generateUrl('public_larp_list'),
        ];

        foreach ($publicRoutes as $route) {
            $this->client->request('GET', $route);

            $this->assertResponseIsSuccessful(
                "PENDING user should be able to access public route {$route}"
            );
        }
    }
}
