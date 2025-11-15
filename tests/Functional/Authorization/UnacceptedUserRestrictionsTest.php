<?php

declare(strict_types=1);

namespace App\Tests\Functional\Authorization;

use App\Tests\Traits\AuthenticationTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
class UnacceptedUserRestrictionsTest extends WebTestCase
{
    use AuthenticationTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
    }



    public function test_pending_user_cannot_access_larp_creation_form(): void
    {
        $pendingUser = $this->createPendingUser();

        $this->client->loginUser($pendingUser);

        $this->client->request('GET', $this->generateUrl('backoffice_larp_create'));

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_pending_user_cannot_submit_larp_creation(): void
    {
        $pendingUser = $this->createPendingUser();

        $this->client->loginUser($pendingUser);

        // Try to POST directly to LARP creation endpoint
        $this->client->request('POST', $this->generateUrl('backoffice_larp_create'), [
            'larp' => [
                'title' => 'Unauthorized LARP',
                'description' => 'This should not be created',
            ],
        ]);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_pending_user_cannot_access_location_creation_form(): void
    {
        $pendingUser = $this->createPendingUser();

        $this->client->loginUser($pendingUser);

        $this->client->request('GET', $this->generateUrl('backoffice_location_modify_global', ['location' => 'new']));

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_pending_user_cannot_submit_location_creation(): void
    {
        $pendingUser = $this->createPendingUser();

        $this->client->loginUser($pendingUser);

        // Try to POST directly to Location creation endpoint
        $this->client->request('POST', $this->generateUrl('backoffice_location_modify_global', ['location' => 'new']), [
            'location' => [
                'name' => 'Unauthorized Location',
                'address' => '123 Test St',
                'city' => 'Test City',
                'country' => 'Test Country',
                'postalCode' => '12345',
            ],
        ]);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_suspended_user_cannot_access_larp_creation_form(): void
    {
        $suspendedUser = $this->createSuspendedUser();

        $this->client->loginUser($suspendedUser);

        $this->client->request('GET', $this->generateUrl('backoffice_larp_create'));

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_suspended_user_cannot_submit_larp_creation(): void
    {
        $suspendedUser = $this->createSuspendedUser();

        $this->client->loginUser($suspendedUser);

        $this->client->request('POST', $this->generateUrl('backoffice_larp_create'), [
            'larp' => [
                'title' => 'Unauthorized LARP',
                'description' => 'This should not be created',
            ],
        ]);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_suspended_user_cannot_access_location_creation_form(): void
    {
        $suspendedUser = $this->createSuspendedUser();

        $this->client->loginUser($suspendedUser);

        $this->client->request('GET', $this->generateUrl('backoffice_location_modify_global', ['location' => 'new']));

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_suspended_user_cannot_submit_location_creation(): void
    {
        $suspendedUser = $this->createSuspendedUser();

        $this->client->loginUser($suspendedUser);

        $this->client->request('POST', $this->generateUrl('backoffice_location_modify_global', ['location' => 'new']), [
            'location' => [
                'name' => 'Unauthorized Location',
                'address' => '123 Test St',
            ],
        ]);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_banned_user_cannot_access_larp_creation_form(): void
    {
        $bannedUser = $this->createBannedUser();

        $this->client->loginUser($bannedUser);

        $this->client->request('GET', $this->generateUrl('backoffice_larp_create'));

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_banned_user_cannot_submit_larp_creation(): void
    {
        $bannedUser = $this->createBannedUser();

        $this->client->loginUser($bannedUser);

        $this->client->request('POST', $this->generateUrl('backoffice_larp_create'), [
            'larp' => [
                'title' => 'Unauthorized LARP',
                'description' => 'This should not be created',
            ],
        ]);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_banned_user_cannot_access_location_creation_form(): void
    {
        $bannedUser = $this->createBannedUser();

        $this->client->loginUser($bannedUser);

        $this->client->request('GET', $this->generateUrl('backoffice_location_modify_global', ['location' => 'new']));

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_banned_user_cannot_submit_location_creation(): void
    {
        $bannedUser = $this->createBannedUser();

        $this->client->loginUser($bannedUser);

        $this->client->request('POST', $this->generateUrl('backoffice_location_modify_global', ['location' => 'new']), [
            'location' => [
                'name' => 'Unauthorized Location',
                'address' => '123 Test St',
            ],
        ]);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_pending_user_cannot_create_larp_entity_directly(): void
    {
        $pendingUser = $this->createPendingUser();

        $this->client->loginUser($pendingUser);

        // Verify voter denies access at service layer
        $authChecker = static::getContainer()->get('security.authorization_checker');

        $canCreate = $authChecker->isGranted('CREATE_LARP');

        $this->assertFalse(
            $canCreate,
            'Voter should deny CREATE_LARP permission for PENDING user'
        );
    }

    public function test_pending_user_cannot_create_location_entity_directly(): void
    {
        $pendingUser = $this->createPendingUser();

        $this->client->loginUser($pendingUser);

        // Verify voter denies access at service layer
        $authChecker = static::getContainer()->get('security.authorization_checker');

        $canCreate = $authChecker->isGranted('CREATE_LOCATION');

        $this->assertFalse(
            $canCreate,
            'Voter should deny CREATE_LOCATION permission for PENDING user'
        );
    }

    public function test_pending_user_sees_appropriate_error_message_for_larp_creation(): void
    {
        $pendingUser = $this->createPendingUser();

        $this->client->loginUser($pendingUser);

        $this->client->request('GET', $this->generateUrl('backoffice_larp_create'));

        // If it's a redirect with flash message, follow and check
        if ($this->client->getResponse()->isRedirect()) {
            $crawler = $this->client->followRedirect();
            $content = $crawler->html();

            $this->assertStringContainsStringIgnoringCase(
                'approved',
                $content,
                'Error message should mention account approval requirement'
            );
        }
    }

    public function test_approved_user_can_access_larp_creation_form(): void
    {
        $approvedUser = $this->createApprovedUser();

        $this->client->loginUser($approvedUser);

        $this->client->request('GET', $this->generateUrl('backoffice_larp_create'));

        $this->assertResponseIsSuccessful(
            'APPROVED user should be able to access LARP creation form'
        );
    }

    public function test_approved_user_can_access_location_creation_form(): void
    {
        $approvedUser = $this->createApprovedUser();

        $this->client->loginUser($approvedUser);

        $this->client->request('GET', $this->generateUrl('backoffice_location_modify_global', ['location' => 'new']));

        $this->assertResponseIsSuccessful(
            'APPROVED user should be able to access Location creation form'
        );
    }
}
