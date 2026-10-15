<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import\Rule;

use App\Domain\Integrations\Miro\Import\MiroImportContext;
use App\Domain\Integrations\Miro\Import\MiroImportRuleInterface;
use App\Domain\Integrations\Miro\Import\MiroImportSuggestion;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 200)]
final readonly class FrameTitleRule implements MiroImportRuleInterface
{
    public const FRAME_TYPES = [TargetType::Faction, TargetType::Thread, TargetType::Place];

    public function suggest(MiroBoardEntry $entry, MiroImportContext $context): ?MiroImportSuggestion
    {
        if (!$entry->isFrame()) {
            return null;
        }

        $matches = [];
        foreach (self::FRAME_TYPES as $frameType) {
            array_push($matches, ...$context->index->matchingType($frameType, $entry->title));
        }

        if ($matches === []) {
            return MiroImportSuggestion::unmapped($entry, MiroImportSuggestion::REASON_NO_FRAME_MATCH);
        }

        if (count($matches) > 1) {
            return MiroImportSuggestion::unmapped($entry, MiroImportSuggestion::REASON_AMBIGUOUS_MATCH);
        }

        return MiroImportSuggestion::link($entry, $matches[0]);
    }
}
