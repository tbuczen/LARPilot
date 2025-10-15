<?php

namespace App\Domain\StoryMarketplace\Service;

use App\Domain\Application\Entity\LarpApplication;
use App\Domain\Application\Repository\LarpApplicationRepository;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Event;
use App\Domain\StoryObject\Entity\Quest;
use App\Domain\StoryObject\Entity\Thread;
use App\Domain\StoryObject\Repository\CharacterRepository;
use App\Domain\StoryObject\Repository\EventRepository;
use App\Domain\StoryObject\Repository\QuestRepository;
use App\Domain\StoryObject\Repository\ThreadRepository;

readonly class MarketplaceService
{
    public function __construct(
        private ThreadRepository          $threadRepository,
        private QuestRepository           $questRepository,
        private EventRepository           $eventRepository,
        private CharacterRepository       $characterRepository,
        private LarpApplicationRepository $applicationRepository
    ) {
    }


    /**
     * Get threads that match player preferences and need characters.
     *
     * @return array{thread: Thread, matchScore: int, matchingTags: array<Tag>, reason: string}[]
     */
    public function getSuggestedThreadsForApplication(LarpApplication $application): array
    {
        $larp = $application->getLarp();
        $preferredTags = $application->getPreferredTags()->toArray();
        $unwantedTags = $application->getUnwantedTags()->toArray();

        $threads = $this->threadRepository->findBy(['larp' => $larp]);

        $suggestions = [];
        foreach ($threads as $thread) {
            $score = $this->calculateMatchScore($thread, $preferredTags, $unwantedTags);

            if ($score['score'] > 0) {
                $suggestions[] = [
                    'thread' => $thread,
                    'matchScore' => $score['score'],
                    'matchingTags' => $score['matchingTags'],
                    'reason' => $this->generateMatchReason($score),
                ];
            }
        }

        // Sort by match score descending
        usort($suggestions, fn ($a, $b) => $b['matchScore'] <=> $a['matchScore']);

        return $suggestions;
    }

    /**
     * Get quests that match player preferences.
     *
     * @return array{quest: Quest, matchScore: int, matchingTags: array<Tag>, reason: string}[]
     */
    public function getSuggestedQuestsForApplication(LarpApplication $application): array
    {
        $larp = $application->getLarp();
        $preferredTags = $application->getPreferredTags()->toArray();
        $unwantedTags = $application->getUnwantedTags()->toArray();

        $quests = $this->questRepository->findBy(['larp' => $larp]);

        $suggestions = [];
        foreach ($quests as $quest) {
            $score = $this->calculateMatchScore($quest, $preferredTags, $unwantedTags);

            if ($score['score'] > 0) {
                $suggestions[] = [
                    'quest' => $quest,
                    'matchScore' => $score['score'],
                    'matchingTags' => $score['matchingTags'],
                    'reason' => $this->generateMatchReason($score),
                ];
            }
        }

        usort($suggestions, fn ($a, $b) => $b['matchScore'] <=> $a['matchScore']);

        return $suggestions;
    }

    /**
     * Get events that match player preferences.
     *
     * @return array{event: Event, matchScore: int, matchingTags: array<Tag>, reason: string}[]
     */
    public function getSuggestedEventsForApplication(LarpApplication $application): array
    {
        $larp = $application->getLarp();
        $preferredTags = $application->getPreferredTags()->toArray();
        $unwantedTags = $application->getUnwantedTags()->toArray();

        $events = $this->eventRepository->findBy(['larp' => $larp]);

        $suggestions = [];
        foreach ($events as $event) {
            $score = $this->calculateMatchScore($event, $preferredTags, $unwantedTags);

            if ($score['score'] > 0) {
                $suggestions[] = [
                    'event' => $event,
                    'matchScore' => $score['score'],
                    'matchingTags' => $score['matchingTags'],
                    'reason' => $this->generateMatchReason($score),
                ];
            }
        }

        usort($suggestions, fn ($a, $b) => $b['matchScore'] <=> $a['matchScore']);

        return $suggestions;
    }

    /**
     * Calculate match score for a story object against player preferences.
     *
     * @param Thread|Quest|Event $storyObject
     * @param Tag[]              $preferredTags
     * @param Tag[]              $unwantedTags
     *
     * @return array{score: int, matchingTags: array<Tag>, unwantedCount: int}
     */
    private function calculateMatchScore(
        Thread|Quest|Event $storyObject,
        array $preferredTags,
        array $unwantedTags
    ): array {
        $objectTags = $storyObject->getTags()->toArray();
        $preferredTagIds = array_map(fn (Tag $t) => $t->getId(), $preferredTags);
        $unwantedTagIds = array_map(fn (Tag $t) => $t->getId(), $unwantedTags);

        $matchingTags = [];
        $matchingCount = 0;
        $unwantedCount = 0;

        foreach ($objectTags as $tag) {
            $tagId = $tag->getId();

            if (in_array($tagId, $preferredTagIds, true)) {
                ++$matchingCount;
                $matchingTags[] = $tag;
            }

            if (in_array($tagId, $unwantedTagIds, true)) {
                ++$unwantedCount;
            }
        }

        // Calculate score: +10 points per matching tag, -20 per unwanted tag
        $score = ($matchingCount * 10) - ($unwantedCount * 20);

        return [
            'score' => $score,
            'matchingTags' => $matchingTags,
            'unwantedCount' => $unwantedCount,
        ];
    }

    /**
     * Generate human-readable match reason.
     *
     * @param array{score: int, matchingTags: array<Tag>, unwantedCount: int} $scoreData
     */
    private function generateMatchReason(array $scoreData): string
    {
        $matchingCount = count($scoreData['matchingTags']);
        $unwantedCount = $scoreData['unwantedCount'];

        if ($matchingCount > 0 && $unwantedCount === 0) {
            return "Matches {$matchingCount} preferred tag(s)";
        }

        if ($matchingCount > 0 && $unwantedCount > 0) {
            return "Matches {$matchingCount} preferred tag(s), but contains {$unwantedCount} unwanted tag(s)";
        }

        if ($matchingCount === 0 && $unwantedCount > 0) {
            return "Contains {$unwantedCount} unwanted tag(s)";
        }

        return 'No tag matches';
    }
}
