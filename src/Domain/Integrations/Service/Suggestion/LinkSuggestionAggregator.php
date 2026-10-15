<?php

namespace App\Domain\Integrations\Service\Suggestion;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\TagRepository;
use App\Domain\Integrations\Entity\Enum\ExternalItemReviewStatus;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalItemSuggestion;
use App\Domain\StoryObject\Repository\StoryObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class LinkSuggestionAggregator
{
    public const MAX_SUGGESTIONS_PER_ITEM = 5;

    /**
     * @param iterable<LinkSuggesterInterface> $suggesters
     */
    public function __construct(
        #[AutowireIterator(LinkSuggesterInterface::TAG)]
        private iterable $suggesters,
        private StoryObjectRepository $storyObjectRepository,
        private TagRepository $tagRepository,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function createContext(Larp $larp): LinkSuggestionContext
    {
        return new LinkSuggestionContext($larp, $this->storyObjectRepository, $this->tagRepository);
    }

    /**
     * @param iterable<ExternalItem> $items
     */
    public function refresh(Larp $larp, iterable $items): void
    {
        $context = null;
        foreach ($items as $item) {
            if ($item->getReviewStatus() !== ExternalItemReviewStatus::Unreviewed) {
                continue;
            }

            $context ??= $this->createContext($larp);
            $this->refreshItem($item, $context);
        }

        if ($context === null) {
            return;
        }

        $this->entityManager->flush();
    }

    public function refreshItem(ExternalItem $item, LinkSuggestionContext $context): void
    {
        $bestCandidates = [];
        foreach ($this->suggesters as $suggester) {
            foreach ($suggester->suggest($item, $context) as $candidate) {
                $key = $candidate->key();
                if (isset($bestCandidates[$key]) && $bestCandidates[$key]->score >= $candidate->score) {
                    continue;
                }

                $bestCandidates[$key] = $candidate;
            }
        }

        $bestCandidates = array_values($bestCandidates);
        usort(
            $bestCandidates,
            static fn (LinkSuggestionCandidate $left, LinkSuggestionCandidate $right): int => [$right->score, $left->key()] <=> [$left->score, $right->key()],
        );

        $topCandidates = array_slice($bestCandidates, 0, self::MAX_SUGGESTIONS_PER_ITEM);
        if ($this->isUnchanged($item, $topCandidates)) {
            $item->markSuggestionsRefreshed($this->clock->now());

            return;
        }

        $suggestions = array_map(
            static fn (LinkSuggestionCandidate $candidate): ExternalItemSuggestion => (new ExternalItemSuggestion($item, $candidate->source, $candidate->score))
                ->setStoryObject($candidate->storyObject)
                ->setTargetType($candidate->targetType)
                ->setTag($candidate->tag),
            $topCandidates,
        );

        $item->replaceSuggestions($suggestions, $this->clock->now());
    }

    /**
     * @param list<LinkSuggestionCandidate> $topCandidates
     */
    private function isUnchanged(ExternalItem $item, array $topCandidates): bool
    {
        if ($item->getSuggestionsRefreshedAt() === null) {
            return false;
        }

        $storedSignatures = array_map(
            static fn (ExternalItemSuggestion $suggestion): string => $suggestion->signature(),
            $item->getSuggestions()->toArray(),
        );
        $candidateSignatures = array_map(
            static fn (LinkSuggestionCandidate $candidate): string => $candidate->signature(),
            $topCandidates,
        );
        sort($storedSignatures);
        sort($candidateSignatures);

        return $storedSignatures === $candidateSignatures;
    }
}
