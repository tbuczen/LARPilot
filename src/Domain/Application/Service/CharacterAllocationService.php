<?php

namespace App\Domain\Application\Service;

use App\Domain\Application\DTO\AllocationSuggestionDTO;
use App\Domain\Application\Entity\LarpApplication;
use App\Domain\Application\Entity\LarpApplicationChoice;
use App\Domain\Application\Repository\LarpApplicationRepository;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Entity\Character;

readonly class CharacterAllocationService
{
    public function __construct(
        private LarpApplicationRepository $applicationRepository,
    ) {
    }

    /**
     * Suggest optimal character allocations using a weighted scoring algorithm
     *
     * @return AllocationSuggestionDTO[]
     */
    public function suggestAllocations(Larp $larp): array
    {
        // Get all applications with choices
        $applications = $this->applicationRepository->createQueryBuilder('a')
            ->leftJoin('a.choices', 'c')
            ->leftJoin('c.character', 'ch')
            ->addSelect('c', 'ch')
            ->where('a.larp = :larp')
            ->setParameter('larp', $larp)
            ->getQuery()
            ->getResult();

        // Build character => applicants mapping
        $characterApplicants = $this->buildCharacterApplicantsMap($applications);

        // Calculate scores for each applicant-character pair
        $scores = $this->calculateScores($characterApplicants);

        // Run allocation algorithm (greedy approach with weighted scores)
        $allocations = $this->allocateCharacters($scores);

        return $allocations;
    }

    /**
     * Build a map of characters to their applicants
     *
     * @param LarpApplication[] $applications
     * @return array<string, array{character: Character, choices: LarpApplicationChoice[]}>
     */
    private function buildCharacterApplicantsMap(array $applications): array
    {
        $characterApplicants = [];

        foreach ($applications as $application) {
            foreach ($application->getChoices() as $choice) {
                $characterId = $choice->getCharacter()->getId()->toRfc4122();

                if (!isset($characterApplicants[$characterId])) {
                    $characterApplicants[$characterId] = [
                        'character' => $choice->getCharacter(),
                        'choices' => [],
                    ];
                }

                $characterApplicants[$characterId]['choices'][] = $choice;
            }
        }

        return $characterApplicants;
    }

    /**
     * Calculate weighted scores for each choice
     *
     * Score formula:
     * - Base score from organizer votes (vote score * 10)
     * - Priority bonus: (6 - priority) * 5 (priority 1 gets 25 points, priority 5 gets 5 points)
     * - Tag match bonus (if implemented)
     *
     * @param array<string, array{character: Character, choices: LarpApplicationChoice[]}> $characterApplicants
     * @return array<string, array{choice: LarpApplicationChoice, score: float}>
     */
    private function calculateScores(array $characterApplicants): array
    {
        $scores = [];

        foreach ($characterApplicants as $characterId => $data) {
            foreach ($data['choices'] as $choice) {
                $choiceId = $choice->getId()->toRfc4122();

                // Vote score (can be negative)
                $voteScore = $choice->getVotes() * 10;

                // Priority score (lower priority number = higher score)
                // Priority 1 = 25 points, Priority 2 = 20 points, etc.
                $priorityScore = max(0, (6 - $choice->getPriority()) * 5);

                // Total score
                $totalScore = $voteScore + $priorityScore;

                $scores[$choiceId] = [
                    'choice' => $choice,
                    'score' => $totalScore,
                    'characterId' => $characterId,
                ];
            }
        }

        return $scores;
    }

    /**
     * Allocate characters using greedy algorithm with score-based selection
     *
     * Algorithm:
     * 1. Sort all choices by score (highest first)
     * 2. Iterate through sorted choices
     * 3. Assign character if both character and applicant are available
     * 4. Mark character and applicant as allocated
     *
     * @param array<string, array{choice: LarpApplicationChoice, score: float, characterId: string}> $scores
     * @return AllocationSuggestionDTO[]
     */
    private function allocateCharacters(array $scores): array
    {
        // Sort by score descending
        uasort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);

        $allocations = [];
        $allocatedCharacters = [];
        $allocatedApplicants = [];

        foreach ($scores as $choiceId => $data) {
            $choice = $data['choice'];
            $characterId = $data['characterId'];
            $applicationId = $choice->getApplication()->getId()->toRfc4122();

            // Skip if character or applicant already allocated
            if (isset($allocatedCharacters[$characterId]) || isset($allocatedApplicants[$applicationId])) {
                continue;
            }

            // Allocate this match
            $allocations[] = new AllocationSuggestionDTO(
                applicationId: $applicationId,
                applicantEmail: $choice->getApplication()->getContactEmail(),
                applicantUserId: $choice->getApplication()->getUser()->getId()->toRfc4122(),
                characterId: $characterId,
                characterTitle: $choice->getCharacter()->getTitle(),
                choiceId: $choiceId,
                priority: $choice->getPriority(),
                voteScore: $choice->getVotes(),
                totalScore: $data['score'],
                justification: $choice->getJustification(),
            );

            // Mark as allocated
            $allocatedCharacters[$characterId] = true;
            $allocatedApplicants[$applicationId] = true;
        }

        // Sort allocations by character name for display
        usort($allocations, fn ($a, $b) => $a->characterTitle <=> $b->characterTitle);

        return $allocations;
    }
}
