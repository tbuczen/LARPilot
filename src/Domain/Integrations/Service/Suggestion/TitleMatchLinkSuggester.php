<?php

namespace App\Domain\Integrations\Service\Suggestion;

use App\Domain\Integrations\Entity\ExternalItem;

final readonly class TitleMatchLinkSuggester implements LinkSuggesterInterface
{
    public const SOURCE = 'name';

    public const MINIMUM_TITLE_LENGTH = 3;

    public const EXACT_MATCH_SCORE = 1.0;

    public const PARTIAL_MATCH_BASE_SCORE = 0.5;

    public const PARTIAL_MATCH_COVERAGE_WEIGHT = 0.4;

    public function suggest(ExternalItem $item, LinkSuggestionContext $context): iterable
    {
        $normalizedItemTitle = TitleNormalizer::normalize($item->getTitle());
        if ($normalizedItemTitle === '') {
            return [];
        }

        $candidates = [];
        foreach ($context->storyObjects() as $storyObject) {
            $normalizedStoryTitle = TitleNormalizer::normalize($storyObject->getTitle());
            if (strlen($normalizedStoryTitle) < self::MINIMUM_TITLE_LENGTH) {
                continue;
            }

            if (!TitleNormalizer::containsPhrase($normalizedItemTitle, $normalizedStoryTitle)) {
                continue;
            }

            $candidates[] = LinkSuggestionCandidate::link(self::SOURCE, $this->score($normalizedItemTitle, $normalizedStoryTitle), $storyObject);
        }

        return $candidates;
    }

    private function score(string $normalizedItemTitle, string $normalizedStoryTitle): float
    {
        if ($normalizedItemTitle === $normalizedStoryTitle) {
            return self::EXACT_MATCH_SCORE;
        }

        $coverage = strlen($normalizedStoryTitle) / strlen($normalizedItemTitle);

        return self::PARTIAL_MATCH_BASE_SCORE + self::PARTIAL_MATCH_COVERAGE_WEIGHT * $coverage;
    }
}
