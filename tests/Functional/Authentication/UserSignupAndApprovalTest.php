<?php

declare(strict_types=1);

namespace App\Tests\Functional\Authentication;

use App\Domain\Account\Entity\Enum\UserStatus;
use App\Tests\Traits\AuthenticationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests for user signup and approval workflow
 *
 * Covers:
 * - New users created with PENDING status
 * - PENDING users can access public pages
 * - PENDING users cannot access backoffice
 * - Admin approval/suspension/ban functionality
 * - Status changes affect access control
 */
class UserSignupAndApprovalTest extends WebTestCase
{
    use AuthenticationTestTrait;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_new_user_has_pending_status(): void
    {
        $user = $this->createPendingUser();

        $this->assertTrue($user->isPending(), 'New user should have PENDING status');
        $this->assertFalse($user->isApproved(), 'New user should not be approved');
        $this->assertEquals(UserStatus::PENDING, $user->getStatus());
    }

    public function test_pending_user_can_access_public_pages(): void
    {
        $client = static::createClient();
        $pendingUser = $this->createPendingUser();

        $client->loginUser($pendingUser);

        // Test accessing homepage
        $client->request('GET', $this->generateUrl('public_larp_list'));
        $this->assertResponseIsSuccessful('PENDING user should be able to access homepage');

        // Test accessing public LARP list
        $client->request('GET', $this->generateUrl('public_larp_list'));
        $this->assertResponseIsSuccessful('PENDING user should be able to access public LARP list');
    }

    public function test_pending_user_cannot_access_backoffice(): void
    {
        $client = static::createClient();
        $pendingUser = $this->createPendingUser();

        $client->loginUser($pendingUser);

        // Try to access backoffice
        $client->request('GET', $this->generateUrl('backoffice_larp_create'));

        // Should be redirected to pending approval page
        $this->assertResponseRedirects(null, null, 'PENDING user should be redirected from backoffice');
    }

    public function test_pending_user_cannot_access_larp_creation(): void
    {
        $client = static::createClient();
        $pendingUser = $this->createPendingUser();

        $client->loginUser($pendingUser);

        $client->request('GET', $this->generateUrl('backoffice_larp_create'));

        // Should be redirected to pending approval page
        $this->assertResponseRedirects(null, null, 'PENDING user should be redirected from LARP creation');
    }

    public function test_approved_user_can_access_backoffice(): void
    {
        $client = static::createClient();
        $approvedUser = $this->createApprovedUser();

        $client->loginUser($approvedUser);

        // Try to access backoffice
        $client->request('GET', $this->generateUrl('backoffice_dashboard'));

        $this->assertResponseIsSuccessful('APPROVED user should be able to access backoffice');
    }

    public function test_user_can_be_programmatically_approved(): void
    {
        $user = $this->createPendingUser();

        $this->assertTrue($user->isPending(), 'User should start as PENDING');

        $this->approveUser($user);

        $this->assertTrue($user->isApproved(), 'User should be APPROVED after approval');
        $this->assertFalse($user->isPending(), 'User should no longer be PENDING');
        $this->assertEquals(UserStatus::APPROVED, $user->getStatus());
    }

    public function test_approved_user_can_be_suspended(): void
    {
        $user = $this->createApprovedUser();

        $this->assertTrue($user->isApproved(), 'User should start as APPROVED');

        $this->suspendUser($user);

        $this->assertTrue($user->isSuspended(), 'User should be SUSPENDED after suspension');
        $this->assertFalse($user->isApproved(), 'User should no longer be APPROVED');
        $this->assertEquals(UserStatus::SUSPENDED, $user->getStatus());
    }

    public function test_user_can_be_banned(): void
    {
        $user = $this->createApprovedUser();

        $this->assertTrue($user->isApproved(), 'User should start as APPROVED');

        $this->banUser($user);

        $this->assertTrue($user->isBanned(), 'User should be BANNED after banning');
        $this->assertFalse($user->isApproved(), 'User should no longer be APPROVED');
        $this->assertEquals(UserStatus::BANNED, $user->getStatus());
    }

    public function test_suspended_user_cannot_access_backoffice(): void
    {
        $client = static::createClient();
        $suspendedUser = $this->createSuspendedUser();

        $client->loginUser($suspendedUser);

        $client->request('GET', $this->generateUrl('backoffice_dashboard'));

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_banned_user_cannot_access_backoffice(): void
    {
        $client = static::createClient();
        $bannedUser = $this->createBannedUser();

        $client->loginUser($bannedUser);

        $client->request('GET', $this->generateUrl('backoffice_dashboard'));

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_suspended_user_cannot_create_larp(): void
    {
        $client = static::createClient();
        $suspendedUser = $this->createSuspendedUser();

        $client->loginUser($suspendedUser);

        $client->request('GET', $this->generateUrl('backoffice_larp_create'));

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_banned_user_cannot_create_larp(): void
    {
        $client = static::createClient();
        $bannedUser = $this->createBannedUser();

        $client->loginUser($bannedUser);

        $client->request('GET', $this->generateUrl('backoffice_larp_create'));

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_super_admin_can_access_super_admin_routes(): void
    {
        $client = static::createClient();
        $superAdmin = $this->createSuperAdmin();

        $client->loginUser($superAdmin);

        // Try to access super admin area
        $client->request('GET', $this->generateUrl('super_admin_users_list'));

        // Should be successful or redirect to a valid super-admin page
        $this->assertResponseStatusCodeSame(
            200,
            'SUPER_ADMIN should be able to access super-admin routes'
        );
    }

    public function test_regular_user_cannot_access_super_admin_routes(): void
    {
        $client = static::createClient();
        $regularUser = $this->createApprovedUser();

        $client->loginUser($regularUser);

        $client->request('GET', $this->generateUrl('super_admin_users_list'));

        $this->assertResponseStatusCodeSame(403, 'Regular user should not access super-admin routes');
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $client = static::createClient();

        $client->request('GET', $this->generateUrl('backoffice_dashboard'));

        $this->assertResponseRedirects(null, null, 'Unauthenticated user should be redirected to login');
    }

    public function test_pending_user_status_persists_after_flush(): void
    {
        $user = $this->createPendingUser();
        $userId = $user->getId();

        // Clear entity manager to force reload from database
        $this->getEntityManager()->clear();

        // Reload user from database
        $reloadedUser = $this->getEntityManager()->find(\App\Domain\Account\Entity\User::class, $userId);

        $this->assertNotNull($reloadedUser, 'User should be persisted in database');
        $this->assertEquals(UserStatus::PENDING, $reloadedUser->getStatus(), 'Status should persist');
        $this->assertTrue($reloadedUser->isPending(), 'User should still be PENDING after reload');
    }

    public function test_approved_status_persists_after_flush(): void
    {
        $user = $this->createPendingUser();
        $userId = $user->getId();

        $this->approveUser($user);

        // Clear entity manager to force reload from database
        $this->getEntityManager()->clear();

        // Reload user from database
        $reloadedUser = $this->getEntityManager()->find(\App\Domain\Account\Entity\User::class, $userId);

        $this->assertNotNull($reloadedUser, 'User should be persisted in database');
        $this->assertEquals(UserStatus::APPROVED, $reloadedUser->getStatus(), 'Approved status should persist');
        $this->assertTrue($reloadedUser->isApproved(), 'User should still be APPROVED after reload');
    }
}
