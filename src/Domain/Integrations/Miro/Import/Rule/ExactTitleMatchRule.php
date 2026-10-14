<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import\Rule;

use App\Domain\Integrations\Miro\Import\MiroImportConfidence;
use App\Domain\Integrations\Miro\Import\MiroImportContext;
use App\Domain\Integrations\Miro\Import\MiroImportRuleInterface;
use App\Domain\Integrations\Miro\Import\MiroImportSuggestion;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 100)]
final readonly class ExactTitleMatchRule implements MiroImportRuleInterface
{
    public function suggest(MiroBoardEntry $entry, MiroImportContext $context): ?MiroImportSuggestion
    {
        if ($entry->isFrame() || $entry->isConnector()) {
            return null;
        }

        $matches = $context->index->matchingExactly($entry->title);
        if ($matches === []) {
            return null;
        }

        if (count($matches) > 1) {
            return MiroImportSuggestion::unmapped($entry, MiroImportSuggestion::REASON_AMBIGUOUS_MATCH);
        }

        return MiroImportSuggestion::link($entry, $matches[0], MiroImportConfidence::Low);
    }
}
