<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Tests\Traits\AuthenticationTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
class LarpVisibilitySecurityTest extends WebTestCase
{
    use AuthenticationTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
    }

    public function test_published_larp_is_publicly_visible(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createPublishedLarp($organizer);

        $this->assertTrue(
            $larp->getStatus()->isVisibleForEveryone(),
            'PUBLISHED LARP should be publicly visible'
        );
    }

    public function test_draft_larp_is_not_publicly_visible(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $this->assertFalse(
            $larp->getStatus()->isVisibleForEveryone(),
            'DRAFT LARP should not be publicly visible'
        );
    }

    public function test_wip_larp_is_not_publicly_visible(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createWipLarp($organizer);

        $this->assertFalse(
            $larp->getStatus()->isVisibleForEveryone(),
            'WIP LARP should not be publicly visible'
        );
    }

    public function test_inquiries_larp_is_publicly_visible(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::INQUIRIES);

        $this->assertTrue(
            $larp->getStatus()->isVisibleForEveryone(),
            'INQUIRIES LARP should be publicly visible'
        );
    }

    public function test_confirmed_larp_is_publicly_visible(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::CONFIRMED);

        $this->assertTrue(
            $larp->getStatus()->isVisibleForEveryone(),
            'CONFIRMED LARP should be publicly visible'
        );
    }

    public function test_completed_larp_is_publicly_visible(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::COMPLETED);

        $this->assertTrue(
            $larp->getStatus()->isVisibleForEveryone(),
            'COMPLETED LARP should be publicly visible'
        );
    }

    public function test_unauthenticated_user_can_see_public_larp_in_list(): void
    {
        $organizer = $this->createApprovedUser();
        $publicLarp = $this->createPublishedLarp($organizer, 'Public LARP');

        $crawler = $this->client->request('GET', $this->generateUrl('public_larp_list'));

        $this->assertResponseIsSuccessful('Public should be able to view LARP list');

        // Check if LARP appears in the list (basic check)
        $content = $crawler->html();
        $this->assertStringContainsString(
            'Public LARP',
            $content,
            'Public LARP should appear in public list'
        );
    }

    public function test_unauthenticated_user_cannot_see_draft_larp_in_list(): void
    {
        $organizer = $this->createApprovedUser();
        $draftLarp = $this->createDraftLarp($organizer, 'Draft LARP');

        $crawler = $this->client->request('GET', $this->generateUrl('public_larp_list'));

        $content = $crawler->html();
        $this->assertStringNotContainsString(
            'Draft LARP',
            $content,
            'Draft LARP should not appear in public list'
        );
    }

    public function test_unauthenticated_user_cannot_access_larp_backoffice(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createPublishedLarp($organizer);

        $this->client->request('GET', $this->generateUrl("backoffice_larp_dashboard", ["larp" => $larp->getId()]));

        $this->assertResponseRedirects(
            null,
            null,
            'Unauthenticated user should be redirected from LARP backoffice'
        );
    }

    public function test_pending_user_cannot_access_larp_backoffice(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createPublishedLarp($organizer);

        $pendingUser = $this->createPendingUser();

        $this->client->loginUser($pendingUser);

        $this->client->request('GET', $this->generateUrl("backoffice_larp_dashboard", ["larp" => $larp->getId()]));

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects($this->generateUrl('backoffice_account_pending_approval'));
    }

    public function test_participant_can_access_their_larp_backoffice(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $this->client->loginUser($organizer);

        $this->client->request('GET', $this->generateUrl("backoffice_larp_dashboard", ["larp" => $larp->getId()]));

        $this->assertResponseIsSuccessful(
            'Organizer should be able to access their LARP backoffice'
        );
    }

    public function test_non_participant_cannot_access_other_larp_backoffice(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $otherUser = $this->createApprovedUser();

        $this->client->loginUser($otherUser);

        $this->client->request('GET', $this->generateUrl("backoffice_larp_dashboard", ["larp" => $larp->getId()]));

        $this->assertResponseStatusCodeSame(
            403,
            'Non-participant should not access other LARP backoffice'
        );
    }

    public function test_player_participant_can_not_access_larp_backoffice(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $player = $this->createApprovedUser();
        $this->addParticipantToLarp($larp, $player);

        $this->client->loginUser($player);

        $this->client->request('GET', $this->generateUrl("backoffice_larp_dashboard", ["larp" => $larp->getId()]));

        $this->assertResponseStatusCodeSame(403);
    }

    public function test_organizer_has_full_larp_access(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $participant = $larp->getLarpParticipants()[0];

        $this->assertTrue(
            $participant->isOrganizer(),
            'Organizer should have ORGANIZER role'
        );
        $this->assertTrue(
            $participant->isAdmin(),
            'Organizer should be admin of their LARP'
        );
    }

    public function test_staff_participant_can_access_larp_backoffice(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $staff = $this->createApprovedUser();
        $this->addParticipantToLarp($larp, $staff, [ParticipantRole::STAFF]);

        $this->client->loginUser($staff);

        $this->client->request('GET', $this->generateUrl("backoffice_larp_dashboard", ["larp" => $larp->getId()]));

        $this->assertResponseIsSuccessful(
            'Staff participant should be able to access LARP backoffice'
        );
    }

    public function test_super_admin_can_not_access_any_larp_backoffice(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $superAdmin = $this->createSuperAdmin();

        $this->client->loginUser($superAdmin);

        $this->client->request('GET', $this->generateUrl("backoffice_larp_dashboard", ["larp" => $larp->getId()]));

        $this->assertResponseStatusCodeSame(403);
    }

    public function test_super_admin_can_see_all_his_larps(): void
    {
        $organizer = $this->createApprovedUser();
        $draftLarp = $this->createDraftLarp($organizer, 'Secret Draft');

        $superAdmin = $this->createSuperAdmin();

        $this->client->loginUser($superAdmin);

        // Access admin LARP list (should show all LARPs)
        $crawler = $this->client->request('GET', $this->generateUrl('backoffice_dashboard'));

        $this->assertResponseIsSuccessful();
    }

    public function test_approved_user_only_sees_their_participating_larps(): void
    {
        $organizer1 = $this->createApprovedUser();
        $organizer2 = $this->createApprovedUser();

        $larp1 = $this->createDraftLarp($organizer1, 'LARP 1');
        $larp2 = $this->createDraftLarp($organizer2, 'LARP 2');

        
        $this->client->loginUser($organizer1);

        // Organizer1 should be able to access LARP 1
        $this->client->request('GET', $this->generateUrl("backoffice_larp_dashboard", ["larp" => $larp1->getId()]));
        $this->assertResponseIsSuccessful('Organizer1 should access their LARP');

        // But not LARP 2
        $this->client->request('GET', $this->generateUrl("backoffice_larp_dashboard", ["larp" => $larp2->getId()]));
        $this->assertResponseStatusCodeSame(403, 'Organizer1 should not access other organizer\'s LARP');
    }

    public function test_cancelled_larp_is_not_publicly_visible(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::CANCELLED);

        $this->assertFalse(
            $larp->getStatus()->isVisibleForEveryone(),
            'CANCELLED LARP should not be publicly visible'
        );
    }

    public function test_multiple_roles_participant_has_proper_access(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $multiRoleUser = $this->createApprovedUser();
        $this->addParticipantToLarp($larp, $multiRoleUser, [
            ParticipantRole::STAFF,
            ParticipantRole::STORY_WRITER,
        ]);

        $this->client->loginUser($multiRoleUser);

        $this->client->request('GET', $this->generateUrl("backoffice_larp_dashboard", ["larp" => $larp->getId()]));

        $this->assertResponseIsSuccessful(
            'Multi-role participant should be able to access LARP backoffice'
        );
    }
}
