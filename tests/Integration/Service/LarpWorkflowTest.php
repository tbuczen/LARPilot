<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Tests\Traits\AuthenticationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Integration tests for LARP workflow state machine
 *
 * Tests workflow transitions and status visibility logic
 */
class LarpWorkflowTest extends KernelTestCase
{
    use AuthenticationTestTrait;

    private ?WorkflowInterface $larpWorkflow = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->clearTestData();

        $this->larpWorkflow = static::getContainer()->get('workflow.larp_stage_status');
    }

    protected function tearDown(): void
    {
        $this->clearTestData();
        parent::tearDown();
    }

    public function test_new_larp_starts_in_draft_status(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createLarp($organizer);

        $this->assertEquals(
            LarpStageStatus::DRAFT,
            $larp->getStatus(),
            'New LARP should start in DRAFT status'
        );
    }

    public function test_draft_is_not_publicly_visible(): void
    {
        $status = LarpStageStatus::DRAFT;

        $this->assertFalse(
            $status->isVisibleForEveryone(),
            'DRAFT status should not be publicly visible'
        );
    }

    public function test_wip_is_not_publicly_visible(): void
    {
        $status = LarpStageStatus::WIP;

        $this->assertFalse(
            $status->isVisibleForEveryone(),
            'WIP status should not be publicly visible'
        );
    }

    public function test_published_is_publicly_visible(): void
    {
        $status = LarpStageStatus::PUBLISHED;

        $this->assertTrue(
            $status->isVisibleForEveryone(),
            'PUBLISHED status should be publicly visible'
        );
    }

    public function test_inquiries_is_publicly_visible(): void
    {
        $status = LarpStageStatus::INQUIRIES;

        $this->assertTrue(
            $status->isVisibleForEveryone(),
            'INQUIRIES status should be publicly visible'
        );
    }

    public function test_confirmed_is_publicly_visible(): void
    {
        $status = LarpStageStatus::CONFIRMED;

        $this->assertTrue(
            $status->isVisibleForEveryone(),
            'CONFIRMED status should be publicly visible'
        );
    }

    public function test_completed_is_publicly_visible(): void
    {
        $status = LarpStageStatus::COMPLETED;

        $this->assertTrue(
            $status->isVisibleForEveryone(),
            'COMPLETED status should be publicly visible'
        );
    }

    public function test_cancelled_is_not_publicly_visible(): void
    {
        $status = LarpStageStatus::CANCELLED;

        $this->assertFalse(
            $status->isVisibleForEveryone(),
            'CANCELLED status should not be publicly visible'
        );
    }

    public function test_workflow_can_transition_from_draft_to_wip(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $canTransition = $this->larpWorkflow->can($larp, 'to_wip');

        $this->assertTrue(
            $canTransition,
            'Workflow should allow transition from DRAFT to WIP'
        );
    }

    public function test_workflow_can_transition_from_draft_to_published(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $canTransition = $this->larpWorkflow->can($larp, 'to_published');

        $this->assertTrue(
            $canTransition,
            'Workflow should allow transition from DRAFT to PUBLISHED'
        );
    }

    public function test_workflow_can_transition_from_wip_to_published(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createWipLarp($organizer);

        $canTransition = $this->larpWorkflow->can($larp, 'to_published');

        $this->assertTrue(
            $canTransition,
            'Workflow should allow transition from WIP to PUBLISHED'
        );
    }

    public function test_workflow_can_transition_from_published_to_inquiries(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createPublishedLarp($organizer);

        $canTransition = $this->larpWorkflow->can($larp, 'to_inquiries');

        $this->assertTrue(
            $canTransition,
            'Workflow should allow transition from PUBLISHED to INQUIRIES'
        );
    }

    public function test_workflow_can_transition_from_inquiries_to_confirmed(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::INQUIRIES);

        $canTransition = $this->larpWorkflow->can($larp, 'to_confirmed');

        $this->assertTrue(
            $canTransition,
            'Workflow should allow transition from INQUIRIES to CONFIRMED'
        );
    }

    public function test_workflow_can_transition_from_confirmed_to_completed(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::CONFIRMED);

        $canTransition = $this->larpWorkflow->can($larp, 'to_completed');

        $this->assertTrue(
            $canTransition,
            'Workflow should allow transition from CONFIRMED to COMPLETED'
        );
    }

    public function test_workflow_can_transition_from_confirmed_to_cancelled(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::CONFIRMED);

        $canTransition = $this->larpWorkflow->can($larp, 'to_cancelled');

        $this->assertTrue(
            $canTransition,
            'Workflow should allow transition from CONFIRMED to CANCELLED'
        );
    }

    public function test_workflow_can_transition_back_from_published_to_draft(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createPublishedLarp($organizer);

        $canTransition = $this->larpWorkflow->can($larp, 'back_to_draft');

        $this->assertTrue(
            $canTransition,
            'Workflow should allow transition back from PUBLISHED to DRAFT'
        );
    }

    public function test_workflow_can_transition_back_from_published_to_wip(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createPublishedLarp($organizer);

        $canTransition = $this->larpWorkflow->can($larp, 'back_to_wip');

        $this->assertTrue(
            $canTransition,
            'Workflow should allow transition back from PUBLISHED to WIP'
        );
    }

    public function test_workflow_applies_transition_correctly(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $this->assertEquals(LarpStageStatus::DRAFT, $larp->getStatus());

        $this->larpWorkflow->apply($larp, 'to_wip');

        $this->assertEquals(
            LarpStageStatus::WIP,
            $larp->getStatus(),
            'Status should change to WIP after applying transition'
        );
    }

    public function test_workflow_marking_is_updated_after_transition(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $this->larpWorkflow->apply($larp, 'to_published');

        $this->assertEquals(
            LarpStageStatus::PUBLISHED->value,
            $larp->getMarking(),
            'Marking should be updated after transition'
        );
    }

    public function test_workflow_cannot_transition_from_completed_to_draft(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::COMPLETED);

        $canTransition = $this->larpWorkflow->can($larp, 'back_to_draft');

        $this->assertFalse(
            $canTransition,
            'Workflow should not allow transition from COMPLETED back to DRAFT'
        );
    }

    public function test_workflow_status_persists_after_transition(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);
        $larpId = $larp->getId();

        $this->larpWorkflow->apply($larp, 'to_published');

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $reloadedLarp = $this->getEntityManager()->find(
            \App\Domain\Core\Entity\Larp::class,
            $larpId
        );

        $this->assertNotNull($reloadedLarp);
        $this->assertEquals(
            LarpStageStatus::PUBLISHED,
            $reloadedLarp->getStatus(),
            'Status should persist after transition'
        );
    }

    public function test_get_enabled_transitions_returns_available_transitions(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $enabledTransitions = $this->larpWorkflow->getEnabledTransitions($larp);

        $transitionNames = array_map(
            fn ($transition) => $transition->getName(),
            $enabledTransitions
        );

        $this->assertContains(
            'to_wip',
            $transitionNames,
            'DRAFT should allow transition to WIP'
        );
        $this->assertContains(
            'to_published',
            $transitionNames,
            'DRAFT should allow transition to PUBLISHED'
        );
    }

    public function test_multiple_transitions_in_sequence(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        // DRAFT -> WIP -> PUBLISHED -> INQUIRIES -> CONFIRMED
        $this->assertEquals(LarpStageStatus::DRAFT, $larp->getStatus());

        $this->larpWorkflow->apply($larp, 'to_wip');
        $this->assertEquals(LarpStageStatus::WIP, $larp->getStatus());

        $this->larpWorkflow->apply($larp, 'to_published');
        $this->assertEquals(LarpStageStatus::PUBLISHED, $larp->getStatus());

        $this->larpWorkflow->apply($larp, 'to_inquiries');
        $this->assertEquals(LarpStageStatus::INQUIRIES, $larp->getStatus());

        $this->larpWorkflow->apply($larp, 'to_confirmed');
        $this->assertEquals(LarpStageStatus::CONFIRMED, $larp->getStatus());
    }

    public function test_workflow_final_state_completed(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::CONFIRMED);

        $this->larpWorkflow->apply($larp, 'to_completed');

        $this->assertEquals(LarpStageStatus::COMPLETED, $larp->getStatus());

        // Cannot go back from completed
        $this->assertFalse($this->larpWorkflow->can($larp, 'back_to_draft'));
        $this->assertFalse($this->larpWorkflow->can($larp, 'back_to_wip'));
    }

    public function test_all_public_statuses_are_visible(): void
    {
        $publicStatuses = [
            LarpStageStatus::PUBLISHED,
            LarpStageStatus::INQUIRIES,
            LarpStageStatus::CONFIRMED,
            LarpStageStatus::COMPLETED,
        ];

        foreach ($publicStatuses as $status) {
            $this->assertTrue(
                $status->isVisibleForEveryone(),
                "{$status->value} should be publicly visible"
            );
        }
    }

    public function test_all_private_statuses_are_not_visible(): void
    {
        $privateStatuses = [
            LarpStageStatus::DRAFT,
            LarpStageStatus::WIP,
            LarpStageStatus::CANCELLED,
        ];

        foreach ($privateStatuses as $status) {
            $this->assertFalse(
                $status->isVisibleForEveryone(),
                "{$status->value} should not be publicly visible"
            );
        }
    }
}
