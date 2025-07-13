<?php

namespace App\Service\Larp\Workflow;

use App\Entity\Larp;
use App\Entity\Enum\LarpStageStatus;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Workflow\Exception\LogicException;
use Doctrine\ORM\EntityManagerInterface;

class LarpWorkflowService
{
    public function __construct(
        private readonly WorkflowInterface      $larpStageStatusStateMachine,
        private readonly EntityManagerInterface $entityManager,
        private readonly LarpTransitionGuardService $guardService
    ) {
    }

    /**
     * Get all possible transitions for a given Larp
     */
    public function getEnabledTransitions(Larp $larp): array
    {
        return $this->larpStageStatusStateMachine->getEnabledTransitions($larp);
    }

    /**
     * Check if a transition is allowed for a given Larp
     */
    public function canTransition(Larp $larp, string $transitionName): bool
    {
        return $this->larpStageStatusStateMachine->can($larp, $transitionName);
    }

    /**
     * Apply a transition to a Larp
     */
    public function applyTransition(Larp $larp, string $transitionName): bool
    {
        try {
            $this->larpStageStatusStateMachine->apply($larp, $transitionName);
            $this->entityManager->flush();
            return true;
        } catch (LogicException $e) {
            return false;
        }
    }

    /**
     * Get the current marking (status) of a Larp
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
        $result = [];

        foreach ($transitions as $transition) {
            $transitionName = $transition->getName();
            $validationErrors = $this->guardService->getValidationErrors($larp, $transitionName);
            
            $result[$transitionName] = [
                'name' => $transitionName,
                'label' => $this->getTransitionLabel($transitionName),
                'to' => $transition->getTos()[0] ?? null,
                'canExecute' => empty($validationErrors),
                'validationErrors' => $validationErrors,
            ];
        }

        return $result;
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
            'to_cancelled' => 'Cancel Event',
            'to_completed' => 'Mark as Completed',
            'back_to_draft' => 'Move back to Draft',
            'back_to_wip' => 'Move back to Work in Progress',
            default => ucfirst(str_replace('_', ' ', $transitionName)),
        };
    }
}