<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import\Rule;

use App\Domain\Integrations\Miro\Import\MiroImportContext;
use App\Domain\Integrations\Miro\Import\MiroImportRuleInterface;
use App\Domain\Integrations\Miro\Import\MiroImportSuggestion;
use App\Domain\Integrations\Miro\Import\MiroTitleNormalizer;
use App\Domain\Integrations\Miro\Import\MiroTypeTags;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 300)]
final readonly class TypeTaggedItemRule implements MiroImportRuleInterface
{
    public const TAGGABLE_ITEM_TYPES = ['card', 'sticky_note', 'app_card'];

    public function suggest(MiroBoardEntry $entry, MiroImportContext $context): ?MiroImportSuggestion
    {
        if (!in_array($entry->type, self::TAGGABLE_ITEM_TYPES, true)) {
            return null;
        }

        $types = MiroTypeTags::typesOf($entry);
        if ($types === []) {
            return null;
        }

        if (count($types) > 1) {
            return MiroImportSuggestion::unmapped($entry, MiroImportSuggestion::REASON_AMBIGUOUS_TAGS);
        }

        if (MiroTitleNormalizer::normalize($entry->title) === '') {
            return MiroImportSuggestion::unmapped($entry, MiroImportSuggestion::REASON_NO_TITLE);
        }

        $matches = $context->index->matchingType($types[0], $entry->title);
        if (count($matches) > 1) {
            return MiroImportSuggestion::unmapped($entry, MiroImportSuggestion::REASON_AMBIGUOUS_MATCH);
        }

        if ($matches === []) {
            return MiroImportSuggestion::create($entry, $types[0]);
        }

        return MiroImportSuggestion::link($entry, $matches[0]);
    }
}
