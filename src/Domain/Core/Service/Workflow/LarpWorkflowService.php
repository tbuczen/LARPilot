<?php

namespace App\Domain\Core\Service\Workflow;

use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Larp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\WorkflowInterface;

readonly class LarpWorkflowService
{
    public function __construct(
        private WorkflowInterface          $larpStageStatusStateMachine,
        private EntityManagerInterface     $entityManager,
        private LarpTransitionGuardService $guardService
    ) {
    }

    /**
     * Get all possible transitions for a given Core
     */
    public function getEnabledTransitions(Larp $larp): array
    {
        return $this->larpStageStatusStateMachine->getEnabledTransitions($larp);
    }

    /**
     * Check if a transition is allowed for a given Core
     */
    public function canTransition(Larp $larp, string $transitionName): bool
    {
        return $this->larpStageStatusStateMachine->can($larp, $transitionName);
    }

    /**
     * Apply a transition to a Core
     */
    public function applyTransition(Larp $larp, string $transitionName): bool
    {
        try {
            $this->larpStageStatusStateMachine->apply($larp, $transitionName);
            $this->entityManager->flush();
            return true;
        } catch (LogicException) {
            return false;
        }
    }

    /**
     * Get the current marking (status) of a Core
     */
    public function getCurrentMarking(Larp $larp): array
    {
        return $this->larpStageStatusStateMachine->getMarking($larp)->getPlaces();
    }

    /**
     * Get all available status options
     */
    public function getAllStatuses(): array
    {
        return LarpStageStatus::cases();
    }

    /**
     * Get available transitions with their labels and validation status
     */
    public function getAvailableTransitionsWithLabels(Larp $larp): array
    {
        $transitions = $this->getEnabledTransitions($larp);
        $primaryTransition = $this->getPrimaryTransition($larp);
        $result = [];

        foreach ($transitions as $transition) {
            $transitionName = $transition->getName();
            $validationErrors = $this->guardService->getValidationErrors($larp, $transitionName);

            $result[$transitionName] = [
                'name' => $transitionName,
                'label' => $this->getTransitionLabel($transitionName),
                'to' => $transition->getTos()[0] ?? null,
                'canExecute' => $validationErrors === [],
                'validationErrors' => $validationErrors,
                'isPrimary' => $transitionName === $primaryTransition,
            ];
        }

        return $result;
    }

    /**
     * Determine the primary (recommended) next transition based on the larp's configured stages.
     */
    public function getPrimaryTransition(Larp $larp): ?string
    {
        $status = $larp->getStatus();

        return match ($status) {
            LarpStageStatus::DRAFT => $larp->isEnableWipStage() ? 'to_wip' : 'to_published',
            LarpStageStatus::WIP => 'to_published',
            LarpStageStatus::PUBLISHED => 'to_inquiries',
            LarpStageStatus::INQUIRIES => $larp->isEnableNegotiationStage()
                ? 'to_negotiation'
                : ($larp->isEnableCostumeCheckStage() ? 'to_costume_check' : 'to_in_progress'),
            LarpStageStatus::NEGOTIATION => $larp->isEnableCostumeCheckStage() ? 'to_costume_check' : 'to_in_progress',
            LarpStageStatus::COSTUME_CHECK => 'to_in_progress',
            LarpStageStatus::CONFIRMED => 'to_in_progress',
            LarpStageStatus::IN_PROGRESS => 'to_completed',
            default => null,
        };
    }

    /**
     * Get the ordered list of stages relevant to this larp's configured workflow.
     */
    public function getConfiguredWorkflowStages(Larp $larp): array
    {
        $stages = [LarpStageStatus::DRAFT];

        if ($larp->isEnableWipStage()) {
            $stages[] = LarpStageStatus::WIP;
        }

        $stages[] = LarpStageStatus::PUBLISHED;
        $stages[] = LarpStageStatus::INQUIRIES;

        if ($larp->isEnableNegotiationStage()) {
            $stages[] = LarpStageStatus::NEGOTIATION;
        }

        if ($larp->isEnableCostumeCheckStage()) {
            $stages[] = LarpStageStatus::COSTUME_CHECK;
        }

        $stages[] = LarpStageStatus::IN_PROGRESS;
        $stages[] = LarpStageStatus::COMPLETED;

        return $stages;
    }

    /**
     * Get validation errors for a specific transition
     */
    public function getTransitionValidationErrors(Larp $larp, string $transitionName): array
    {
        return $this->guardService->getValidationErrors($larp, $transitionName);
    }

    /**
     * Get human-readable label for transition
     */
    private function getTransitionLabel(string $transitionName): string
    {
        return match ($transitionName) {
            'to_wip' => 'Move to Work in Progress',
            'to_published' => 'Publish',
            'to_inquiries' => 'Open for Inquiries',
            'to_confirmed' => 'Confirm Event',
            'to_negotiation' => 'Open Character Negotiation',
            'to_costume_check' => 'Start Costume Check',
            'to_in_progress' => 'Mark as In Progress',
            'to_cancelled' => 'Cancel Event',
            'to_completed' => 'Mark as Completed',
            'back_to_draft' => 'Move back to Draft',
            'back_to_wip' => 'Move back to Work in Progress',
            default => ucfirst(str_replace('_', ' ', $transitionName)),
        };
    }
}
