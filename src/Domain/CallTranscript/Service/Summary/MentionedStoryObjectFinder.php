<?php

namespace App\Domain\CallTranscript\Service\Summary;

use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Service\Suggestion\TitleMatchLinkSuggester;
use App\Domain\Integrations\Service\Suggestion\TitleNormalizer;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Repository\StoryObjectRepository;

final readonly class MentionedStoryObjectFinder
{
    public const LINKABLE_TYPES = [
        TargetType::Character,
        TargetType::Faction,
        TargetType::Thread,
        TargetType::Quest,
        TargetType::Place,
        TargetType::Item,
        TargetType::Event,
    ];

    private const LINE_PREFIX_PATTERN = '/^\[[^\]]*\]\s[^:]*:\s/u';

    public function __construct(
        private StoryObjectRepository $storyObjectRepository,
    ) {
    }

    /**
     * @return list<StoryObject>
     */
    public function linkableStoryObjects(Larp $larp): array
    {
        return array_values(array_filter(
            $this->storyObjectRepository->findBy(['larp' => $larp], ['title' => 'ASC']),
            static fn (StoryObject $storyObject): bool => in_array($storyObject::getTargetType(), self::LINKABLE_TYPES, true),
        ));
    }

    /**
     * @param list<string> $transcriptLines
     * @param list<string> $mentions
     *
     * @return list<StoryObject>
     */
    public function find(Larp $larp, array $transcriptLines, array $mentions): array
    {
        $spokenText = array_map(static fn (string $line): string => (string) preg_replace(self::LINE_PREFIX_PATTERN, '', $line), $transcriptLines);
        $normalizedTranscript = TitleNormalizer::normalize(implode(' ', $spokenText));
        $normalizedMentions = array_map(TitleNormalizer::normalize(...), $mentions);

        $found = [];
        foreach ($this->linkableStoryObjects($larp) as $storyObject) {
            $normalizedTitle = TitleNormalizer::normalize($storyObject->getTitle());
            if (strlen($normalizedTitle) < TitleMatchLinkSuggester::MINIMUM_TITLE_LENGTH) {
                continue;
            }

            if (!$this->isMentioned($normalizedTitle, $normalizedTranscript, $normalizedMentions)) {
                continue;
            }

            $found[] = $storyObject;
        }

        usort($found, static fn (StoryObject $left, StoryObject $right): int => [
            array_search($left::getTargetType(), self::LINKABLE_TYPES, true),
            mb_strtolower((string) $left->getTitle()),
        ] <=> [
            array_search($right::getTargetType(), self::LINKABLE_TYPES, true),
            mb_strtolower((string) $right->getTitle()),
        ]);

        return $found;
    }

    /**
     * @param list<string> $normalizedMentions
     */
    private function isMentioned(string $normalizedTitle, string $normalizedTranscript, array $normalizedMentions): bool
    {
        if (TitleNormalizer::containsPhrase($normalizedTranscript, $normalizedTitle)) {
            return true;
        }

        foreach ($normalizedMentions as $normalizedMention) {
            if ($normalizedMention === $normalizedTitle || TitleNormalizer::containsPhrase($normalizedMention, $normalizedTitle)) {
                return true;
            }
        }

        return false;
    }
}
