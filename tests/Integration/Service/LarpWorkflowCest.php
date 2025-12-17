<?php

declare(strict_types=1);

namespace Tests\Integration\Service;

use App\Domain\Core\Entity\Enum\LarpStageStatus;
use Symfony\Component\Workflow\WorkflowInterface;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\FunctionalTester;

/**
 * Integration tests for LARP workflow state machine
 *
 * Tests workflow transitions and status visibility logic
 */
class LarpWorkflowCest
{
    private ?WorkflowInterface $larpWorkflow = null;

    public function _before(FunctionalTester $I): void
    {
        $this->larpWorkflow = $I->grabService('workflow.larp_stage_status');
    }

    public function newLarpStartsInDraftStatus(FunctionalTester $I): void
    {
        $I->wantTo('verify that new LARP starts in DRAFT status');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createLarp($organizer);

        $I->assertEquals(
            LarpStageStatus::DRAFT,
            $larp->getStatus(),
            'New LARP should start in DRAFT status'
        );
    }

    public function draftIsNotPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that DRAFT status is not publicly visible');

        $status = LarpStageStatus::DRAFT;

        $I->assertFalse(
            $status->isVisibleForEveryone(),
            'DRAFT status should not be publicly visible'
        );
    }

    public function wipIsNotPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that WIP status is not publicly visible');

        $status = LarpStageStatus::WIP;

        $I->assertFalse(
            $status->isVisibleForEveryone(),
            'WIP status should not be publicly visible'
        );
    }

    public function publishedIsPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that PUBLISHED status is publicly visible');

        $status = LarpStageStatus::PUBLISHED;

        $I->assertTrue(
            $status->isVisibleForEveryone(),
            'PUBLISHED status should be publicly visible'
        );
    }

    public function inquiriesIsPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that INQUIRIES status is publicly visible');

        $status = LarpStageStatus::INQUIRIES;

        $I->assertTrue(
            $status->isVisibleForEveryone(),
            'INQUIRIES status should be publicly visible'
        );
    }

    public function confirmedIsPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that CONFIRMED status is publicly visible');

        $status = LarpStageStatus::CONFIRMED;

        $I->assertTrue(
            $status->isVisibleForEveryone(),
            'CONFIRMED status should be publicly visible'
        );
    }

    public function completedIsPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that COMPLETED status is publicly visible');

        $status = LarpStageStatus::COMPLETED;

        $I->assertTrue(
            $status->isVisibleForEveryone(),
            'COMPLETED status should be publicly visible'
        );
    }

    public function cancelledIsNotPubliclyVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that CANCELLED status is not publicly visible');

        $status = LarpStageStatus::CANCELLED;

        $I->assertFalse(
            $status->isVisibleForEveryone(),
            'CANCELLED status should not be publicly visible'
        );
    }

    public function workflowCanTransitionFromDraftToWip(FunctionalTester $I): void
    {
        $I->wantTo('verify that workflow allows transition from DRAFT to WIP');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $canTransition = $this->larpWorkflow->can($larp, 'to_wip');

        $I->assertTrue(
            $canTransition,
            'Workflow should allow transition from DRAFT to WIP'
        );
    }

    public function workflowCanTransitionFromDraftToPublished(FunctionalTester $I): void
    {
        $I->wantTo('verify that workflow allows transition from DRAFT to PUBLISHED');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $canTransition = $this->larpWorkflow->can($larp, 'to_published');

        $I->assertTrue(
            $canTransition,
            'Workflow should allow transition from DRAFT to PUBLISHED'
        );
    }

    public function workflowCanTransitionFromWipToPublished(FunctionalTester $I): void
    {
        $I->wantTo('verify that workflow allows transition from WIP to PUBLISHED');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createWipLarp($organizer);

        $canTransition = $this->larpWorkflow->can($larp, 'to_published');

        $I->assertTrue(
            $canTransition,
            'Workflow should allow transition from WIP to PUBLISHED'
        );
    }

    public function workflowCanTransitionFromPublishedToInquiries(FunctionalTester $I): void
    {
        $I->wantTo('verify that workflow allows transition from PUBLISHED to INQUIRIES');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createPublishedLarp($organizer);

        $canTransition = $this->larpWorkflow->can($larp, 'to_inquiries');

        $I->assertTrue(
            $canTransition,
            'Workflow should allow transition from PUBLISHED to INQUIRIES'
        );
    }

    public function workflowCanTransitionFromInquiriesToConfirmed(FunctionalTester $I): void
    {
        $I->wantTo('verify that workflow allows transition from INQUIRIES to CONFIRMED');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createLarp($organizer, LarpStageStatus::INQUIRIES);

        $canTransition = $this->larpWorkflow->can($larp, 'to_confirmed');

        $I->assertTrue(
            $canTransition,
            'Workflow should allow transition from INQUIRIES to CONFIRMED'
        );
    }

    public function workflowCanTransitionFromConfirmedToCompleted(FunctionalTester $I): void
    {
        $I->wantTo('verify that workflow allows transition from CONFIRMED to COMPLETED');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createLarp($organizer, LarpStageStatus::CONFIRMED);

        $canTransition = $this->larpWorkflow->can($larp, 'to_completed');

        $I->assertTrue(
            $canTransition,
            'Workflow should allow transition from CONFIRMED to COMPLETED'
        );
    }

    public function workflowCanTransitionFromConfirmedToCancelled(FunctionalTester $I): void
    {
        $I->wantTo('verify that workflow allows transition from CONFIRMED to CANCELLED');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createLarp($organizer, LarpStageStatus::CONFIRMED);

        $canTransition = $this->larpWorkflow->can($larp, 'to_cancelled');

        $I->assertTrue(
            $canTransition,
            'Workflow should allow transition from CONFIRMED to CANCELLED'
        );
    }

    public function workflowCanTransitionBackFromPublishedToDraft(FunctionalTester $I): void
    {
        $I->wantTo('verify that workflow allows transition back from PUBLISHED to DRAFT');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createPublishedLarp($organizer);

        $canTransition = $this->larpWorkflow->can($larp, 'back_to_draft');

        $I->assertTrue(
            $canTransition,
            'Workflow should allow transition back from PUBLISHED to DRAFT'
        );
    }

    public function workflowCanTransitionBackFromPublishedToWip(FunctionalTester $I): void
    {
        $I->wantTo('verify that workflow allows transition back from PUBLISHED to WIP');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createPublishedLarp($organizer);

        $canTransition = $this->larpWorkflow->can($larp, 'back_to_wip');

        $I->assertTrue(
            $canTransition,
            'Workflow should allow transition back from PUBLISHED to WIP'
        );
    }

    public function workflowAppliesTransitionCorrectly(FunctionalTester $I): void
    {
        $I->wantTo('verify that workflow applies transition correctly');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->assertEquals(LarpStageStatus::DRAFT, $larp->getStatus());

        $this->larpWorkflow->apply($larp, 'to_wip');

        $I->assertEquals(
            LarpStageStatus::WIP,
            $larp->getStatus(),
            'Status should change to WIP after applying transition'
        );
    }

    public function workflowMarkingIsUpdatedAfterTransition(FunctionalTester $I): void
    {
        $I->wantTo('verify that workflow marking is updated after transition');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $this->larpWorkflow->apply($larp, 'to_published');

        $I->assertEquals(
            LarpStageStatus::PUBLISHED->value,
            $larp->getMarking(),
            'Marking should be updated after transition'
        );
    }

    public function workflowCannotTransitionFromCompletedToDraft(FunctionalTester $I): void
    {
        $I->wantTo('verify that workflow does not allow transition from COMPLETED back to DRAFT');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createLarp($organizer, LarpStageStatus::COMPLETED);

        $canTransition = $this->larpWorkflow->can($larp, 'back_to_draft');

        $I->assertFalse(
            $canTransition,
            'Workflow should not allow transition from COMPLETED back to DRAFT'
        );
    }

    public function workflowStatusPersistsAfterTransition(FunctionalTester $I): void
    {
        $I->wantTo('verify that workflow status persists after transition');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $larpId = $larp->getId();

        $this->larpWorkflow->apply($larp, 'to_published');

        $I->getEntityManager()->flush();
        $I->getEntityManager()->clear();

        $reloadedLarp = $I->getEntityManager()->find(
            \App\Domain\Core\Entity\Larp::class,
            $larpId
        );

        $I->assertNotNull($reloadedLarp);
        $I->assertEquals(
            LarpStageStatus::PUBLISHED,
            $reloadedLarp->getStatus(),
            'Status should persist after transition'
        );
    }

    public function getEnabledTransitionsReturnsAvailableTransitions(FunctionalTester $I): void
    {
        $I->wantTo('verify that getEnabledTransitions returns available transitions');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $enabledTransitions = $this->larpWorkflow->getEnabledTransitions($larp);

        $transitionNames = array_map(
            fn ($transition) => $transition->getName(),
            $enabledTransitions
        );

        $I->assertContains(
            'to_wip',
            $transitionNames,
            'DRAFT should allow transition to WIP'
        );
        $I->assertContains(
            'to_published',
            $transitionNames,
            'DRAFT should allow transition to PUBLISHED'
        );
    }

    public function multipleTransitionsInSequence(FunctionalTester $I): void
    {
        $I->wantTo('verify that multiple transitions work in sequence');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        // DRAFT -> WIP -> PUBLISHED -> INQUIRIES -> CONFIRMED
        $I->assertEquals(LarpStageStatus::DRAFT, $larp->getStatus());

        $this->larpWorkflow->apply($larp, 'to_wip');
        $I->assertEquals(LarpStageStatus::WIP, $larp->getStatus());

        $this->larpWorkflow->apply($larp, 'to_published');
        $I->assertEquals(LarpStageStatus::PUBLISHED, $larp->getStatus());

        $this->larpWorkflow->apply($larp, 'to_inquiries');
        $I->assertEquals(LarpStageStatus::INQUIRIES, $larp->getStatus());

        $this->larpWorkflow->apply($larp, 'to_confirmed');
        $I->assertEquals(LarpStageStatus::CONFIRMED, $larp->getStatus());
    }

    public function workflowFinalStateCompleted(FunctionalTester $I): void
    {
        $I->wantTo('verify that COMPLETED is a final state');

        $organizer = UserFactory::createApprovedUser();
        $larp = $I->createLarp($organizer, LarpStageStatus::CONFIRMED);

        $this->larpWorkflow->apply($larp, 'to_completed');

        $I->assertEquals(LarpStageStatus::COMPLETED, $larp->getStatus());

        // Cannot go back from completed
        $I->assertFalse($this->larpWorkflow->can($larp, 'back_to_draft'));
        $I->assertFalse($this->larpWorkflow->can($larp, 'back_to_wip'));
    }

    public function allPublicStatusesAreVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that all public statuses are visible');

        $publicStatuses = [
            LarpStageStatus::PUBLISHED,
            LarpStageStatus::INQUIRIES,
            LarpStageStatus::CONFIRMED,
            LarpStageStatus::COMPLETED,
        ];

        foreach ($publicStatuses as $status) {
            $I->assertTrue(
                $status->isVisibleForEveryone(),
                "{$status->value} should be publicly visible"
            );
        }
    }

    public function allPrivateStatusesAreNotVisible(FunctionalTester $I): void
    {
        $I->wantTo('verify that all private statuses are not visible');

        $privateStatuses = [
            LarpStageStatus::DRAFT,
            LarpStageStatus::WIP,
            LarpStageStatus::CANCELLED,
        ];

        foreach ($privateStatuses as $status) {
            $I->assertFalse(
                $status->isVisibleForEveryone(),
                "{$status->value} should not be publicly visible"
            );
        }
    }
}
