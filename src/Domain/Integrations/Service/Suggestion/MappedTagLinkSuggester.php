<?php

namespace App\Domain\Integrations\Service\Suggestion;

use App\Domain\Core\Entity\Tag;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\StoryObject\Entity\Enum\TargetType;

final readonly class MappedTagLinkSuggester implements LinkSuggesterInterface
{
    public const SOURCE = 'tag';

    public const TYPED_MAPPING_SCORE = 0.9;

    public const TAG_ONLY_MAPPING_SCORE = 0.6;

    public function suggest(ExternalItem $item, LinkSuggestionContext $context): iterable
    {
        $appliedTags = $item->getRaw()['appliedTags'] ?? [];
        if (!is_array($appliedTags)) {
            return [];
        }

        $candidates = [];
        foreach ($appliedTags as $appliedTag) {
            if (!is_array($appliedTag)) {
                continue;
            }

            $targetType = is_string($appliedTag['targetType'] ?? null) ? TargetType::tryFrom($appliedTag['targetType']) : null;
            $tag = is_string($appliedTag['tagId'] ?? null) ? $context->tag($appliedTag['tagId']) : null;
            if ($targetType === null && !$tag instanceof Tag) {
                continue;
            }

            $score = $targetType !== null ? self::TYPED_MAPPING_SCORE : self::TAG_ONLY_MAPPING_SCORE;
            $candidates[] = LinkSuggestionCandidate::create(self::SOURCE, $score, $targetType, $tag);
        }

        return $candidates;
    }
}
