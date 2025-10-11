<?php

namespace App\Service\StoryObject;

use App\Entity\Larp;
use App\Entity\LarpApplication;
use App\Entity\StoryObject\Character;
use App\Entity\StoryObject\Event;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\Thread;
use App\Entity\Tag;
use App\Repository\LarpApplicationRepository;
use App\Repository\StoryObject\CharacterRepository;
use App\Repository\StoryObject\EventRepository;
use App\Repository\StoryObject\QuestRepository;
use App\Repository\StoryObject\ThreadRepository;

class MarketplaceService
{
    public function __construct(
        private readonly ThreadRepository $threadRepository,
        private readonly QuestRepository $questRepository,
        private readonly EventRepository $eventRepository,
        private readonly CharacterRepository $characterRepository,
        private readonly LarpApplicationRepository $applicationRepository
    ) {
    }

    /**
     * Get characters that need more threads (below minimum threshold).
     *
     * @return array<Character>
     */
    public function getCharactersNeedingThreads(Larp $larp): array
    {
        $minThreads = $larp->getMinThreadsPerCharacter();

        $qb = $this->characterRepository->createQueryBuilder('c');
        $qb->leftJoin('c.threads', 't')
            ->where('c.larp = :larp')
            ->setParameter('larp', $larp)
            ->groupBy('c.id')
            ->having('COUNT(t.id) < :minThreads')
            ->setParameter('minThreads', $minThreads)
            ->orderBy('COUNT(t.id)', 'ASC');

        return $qb->getQuery()->getResult();
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
     * Search threads by tags.
     *
     * @param Tag[] $tags
     *
     * @return Thread[]
     */
    public function searchThreadsByTags(Larp $larp, array $tags): array
    {
        if (empty($tags)) {
            return [];
        }

        $qb = $this->threadRepository->createQueryBuilder('t');
        $qb->join('t.tags', 'tag')
            ->where('t.larp = :larp')
            ->andWhere('tag IN (:tags)')
            ->setParameter('larp', $larp)
            ->setParameter('tags', $tags)
            ->groupBy('t.id')
            ->orderBy('COUNT(tag.id)', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Search characters by tags for thread recruitment.
     *
     * @param Tag[] $tags
     *
     * @return Character[]
     */
    public function searchCharactersByTags(Larp $larp, array $tags, bool $onlyNeedingThreads = false): array
    {
        if (empty($tags)) {
            return [];
        }

        $qb = $this->characterRepository->createQueryBuilder('c');
        $qb->join('c.tags', 'tag')
            ->where('c.larp = :larp')
            ->andWhere('tag IN (:tags)')
            ->setParameter('larp', $larp)
            ->setParameter('tags', $tags);

        if ($onlyNeedingThreads) {
            $minThreads = $larp->getMinThreadsPerCharacter();
            $qb->leftJoin('c.threads', 't')
                ->groupBy('c.id')
                ->having('COUNT(t.id) < :minThreads')
                ->setParameter('minThreads', $minThreads);
        }

        $qb->orderBy('c.title', 'ASC');

        return $qb->getQuery()->getResult();
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
