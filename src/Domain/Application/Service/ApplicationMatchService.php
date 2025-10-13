<?php

namespace App\Domain\Application\Service;

use App\Domain\Account\Entity\User;
use App\Domain\Application\DTO\ApplicationChoiceDTO;
use App\Domain\Application\DTO\CharacterMatchDTO;
use App\Domain\Application\DTO\UserVoteDTO;
use App\Domain\Application\DTO\VoteDetailDTO;
use App\Domain\Application\DTO\VoteStatsDTO;
use App\Domain\Application\Entity\LarpApplicationChoice;
use App\Domain\Application\Entity\LarpApplicationVote;
use App\Domain\Application\Repository\LarpApplicationChoiceRepository;
use App\Domain\Application\Repository\LarpApplicationVoteRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class ApplicationMatchService
{
    public function __construct(
        private LarpApplicationChoiceRepository $choiceRepository,
        private LarpApplicationVoteRepository $voteRepository,
    ) {
    }

    /**
     * Get matched application data with all votes preloaded
     *
     * @return CharacterMatchDTO[]
     */
    public function getMatchData(QueryBuilder $qb): array
    {
        // Load all choices with relationships in one query
        $choices = $this->choiceRepository->findForMatchingWithRelations($qb);

        if (empty($choices)) {
            return [];
        }

        // Extract all choice IDs
        $choiceIds = array_map(
            fn (LarpApplicationChoice $c) => $c->getId()->toRfc4122(),
            $choices
        );

        // Load all votes in a single query
        $votesGrouped = $this->choiceRepository->findVotesGroupedByChoice($choiceIds);

        // Build vote stats for each choice
        $voteStatsMap = $this->buildVoteStatsMap($votesGrouped);

        // Group choices by character
        return $this->groupChoicesByCharacter($choices, $voteStatsMap);
    }

    /**
     * Transform paginated choices into CharacterMatchDTO array
     * This is used when pagination is applied at QueryBuilder level
     *
     * @param iterable<LarpApplicationChoice> $paginatedChoices
     * @return CharacterMatchDTO[]
     */
    public function transformPaginatedChoicesToDTOs(iterable $paginatedChoices): array
    {
        // Convert iterable to array
        $choices = is_array($paginatedChoices) ? $paginatedChoices : iterator_to_array($paginatedChoices);

        if (empty($choices)) {
            return [];
        }

        // Extract all choice IDs
        $choiceIds = array_map(
            fn (LarpApplicationChoice $c) => $c->getId()->toRfc4122(),
            $choices
        );

        // Load all votes in a single query
        $votesGrouped = $this->choiceRepository->findVotesGroupedByChoice($choiceIds);

        // Build vote stats for each choice
        $voteStatsMap = $this->buildVoteStatsMap($votesGrouped);

        // Group choices by character
        return $this->groupChoicesByCharacter($choices, $voteStatsMap);
    }

    /**
     * Get user's votes indexed by choice ID
     *
     * @return array<string, UserVoteDTO>
     */
    public function getUserVotes(?UserInterface $user): array
    {
        if (!$user instanceof User) {
            return [];
        }

        $votes = $this->voteRepository->findBy(['user' => $user]);

        $userVotes = [];
        foreach ($votes as $vote) {
            $choiceId = $vote->getChoice()->getId()->toRfc4122();
            $userVotes[$choiceId] = new UserVoteDTO(
                choiceId: $choiceId,
                vote: $vote->getVote(),
                justification: $vote->getJustification(),
            );
        }

        return $userVotes;
    }

    /**
     * @param LarpApplicationVote $votesGrouped
     * @return array<string, VoteStatsDTO>
     */
    private function buildVoteStatsMap(array $votesGrouped): array
    {
        $statsMap = [];

        foreach ($votesGrouped as $choiceId => $votes) {
            $upvotes = 0;
            $downvotes = 0;
            $details = [];

            foreach ($votes as $vote) {
                if ($vote->isUpvote()) {
                    $upvotes++;
                } else {
                    $downvotes++;
                }

                $details[] = new VoteDetailDTO(
                    userId: $vote->getUser()->getId()->toRfc4122(),
                    username: $vote->getUser()->getUsername() ?? $vote->getUser()->getContactEmail(),
                    vote: $vote->getVote(),
                    justification: $vote->getJustification(),
                    createdAt: $vote->getCreatedAt(),
                );
            }

            $statsMap[$choiceId] = new VoteStatsDTO(
                upvotes: $upvotes,
                downvotes: $downvotes,
                total: $upvotes - $downvotes,
                details: $details,
            );
        }

        return $statsMap;
    }

    /**
     * @param LarpApplicationChoice[] $choices
     * @param array<string, VoteStatsDTO> $voteStatsMap
     * @return CharacterMatchDTO[]
     */
    private function groupChoicesByCharacter(array $choices, array $voteStatsMap): array
    {
        $grouped = [];

        foreach ($choices as $choice) {
            $characterId = $choice->getCharacter()->getId()->toRfc4122();
            $choiceId = $choice->getId()->toRfc4122();

            if (!isset($grouped[$characterId])) {
                $grouped[$characterId] = [
                    'characterId' => $characterId,
                    'characterTitle' => $choice->getCharacter()->getTitle(),
                    'choices' => [],
                ];
            }

            $voteStats = $voteStatsMap[$choiceId] ?? new VoteStatsDTO(0, 0, 0, []);

            $grouped[$characterId]['choices'][] = new ApplicationChoiceDTO(
                id: $choiceId,
                characterId: $characterId,
                characterTitle: $choice->getCharacter()->getTitle(),
                applicationId: $choice->getApplication()->getId()->toRfc4122(),
                applicantEmail: $choice->getApplication()->getContactEmail(),
                priority: $choice->getPriority(),
                justification: $choice->getJustification(),
                visual: $choice->getVisual(),
                voteScore: $choice->getVotes(),
                voteStats: $voteStats,
            );
        }

        // Convert to DTOs
        return array_map(
            fn (array $data) => new CharacterMatchDTO(
                characterId: $data['characterId'],
                characterTitle: $data['characterTitle'],
                choices: $data['choices'],
            ),
            array_values($grouped)
        );
    }
}
