<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import\Rule;

use App\Domain\Integrations\Miro\Import\MiroImportAction;
use App\Domain\Integrations\Miro\Import\MiroImportContext;
use App\Domain\Integrations\Miro\Import\MiroImportRuleInterface;
use App\Domain\Integrations\Miro\Import\MiroImportSuggestion;
use App\Domain\Integrations\Miro\Import\MiroImportTarget;
use App\Domain\Integrations\Miro\Import\MiroRelationCaptionMatcher;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;
use App\Domain\StoryObject\Entity\StoryObject;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 100)]
final readonly class ConnectorRelationRule implements MiroImportRuleInterface
{
    public function suggest(MiroBoardEntry $entry, MiroImportContext $context): ?MiroImportSuggestion
    {
        if (!$entry->isConnector()) {
            return null;
        }

        $from = $context->target($entry->startItemId);
        $to = $context->target($entry->endItemId);
        if (!$from instanceof MiroImportTarget || !$to instanceof MiroImportTarget) {
            return MiroImportSuggestion::unmapped($entry, MiroImportSuggestion::REASON_ENDPOINT_UNMAPPED);
        }

        $relationType = MiroRelationCaptionMatcher::match($entry->text);
        if ($relationType === null) {
            return MiroImportSuggestion::relation($entry, MiroImportAction::NeedsType, $from, $to);
        }

        if (!in_array($to->type, $relationType->getCompatibleTargetTypesFor($from->type), true)) {
            return MiroImportSuggestion::relation($entry, MiroImportAction::Incompatible, $from, $to, $relationType);
        }

        if ($from->storyObject instanceof StoryObject
            && $to->storyObject instanceof StoryObject
            && $context->relationExists($from->storyObject, $to->storyObject, $relationType)) {
            return MiroImportSuggestion::relation($entry, MiroImportAction::Unchanged, $from, $to, $relationType);
        }

        return MiroImportSuggestion::relation($entry, MiroImportAction::CreateRelation, $from, $to, $relationType);
    }
}
