<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardSnapshot;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Repository\RelationRepository;
use App\Domain\StoryObject\Repository\StoryObjectRepository;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class MiroImportPlanner
{
    /**
     * @param iterable<MiroImportRuleInterface> $rules
     */
    public function __construct(
        #[AutowireIterator(MiroImportRuleInterface::TAG)]
        private iterable $rules,
        private StoryObjectRepository $storyObjectRepository,
        private RelationRepository $relationRepository,
        private ExternalReferenceRepository $referenceRepository,
    ) {
    }

    public function plan(ExternalSource $board, MiroBoardSnapshot $snapshot): MiroImportPlan
    {
        $larp = $board->getIntegration()->getLarp();

        $linkedObjectsByExternalId = [];
        foreach ($this->referenceRepository->findForLarp($larp, LarpIntegrationProvider::Miro) as $reference) {
            $storyObject = $reference->getStoryObject();
            if ($storyObject instanceof StoryObject) {
                $linkedObjectsByExternalId[$reference->getExternalId()][] = $storyObject;
            }
        }

        return $this->planFor(new MiroImportContext(
            larp: $larp,
            snapshot: $snapshot,
            index: new MiroStoryObjectIndex($this->storyObjectRepository->findBy(['larp' => $larp])),
            linkedObjectsByExternalId: $linkedObjectsByExternalId,
            existingRelations: $this->relationRepository->findBy(['larp' => $larp]),
        ));
    }

    public function planFor(MiroImportContext $context): MiroImportPlan
    {
        $suggestions = [];
        foreach ($context->snapshot->entries as $entry) {
            if ($entry->isConnector()) {
                continue;
            }
            $suggestion = $this->suggestForItem($entry, $context);
            if ($suggestion->target instanceof MiroImportTarget) {
                $context->recordTarget($entry->externalId, $suggestion->target);
            }
            $suggestions[] = $suggestion;
        }

        foreach ($context->snapshot->connectors() as $connector) {
            $suggestions[] = $this->suggestForConnector($connector, $context);
        }

        return new MiroImportPlan($suggestions, $this->membershipHints($context));
    }

    private function suggestForItem(MiroBoardEntry $entry, MiroImportContext $context): MiroImportSuggestion
    {
        $linkedObject = $this->preferredLinkedObject($entry, $context->linkedObjects($entry->externalId));
        if ($linkedObject instanceof StoryObject) {
            return new MiroImportSuggestion($entry, MiroImportAction::Unchanged, target: MiroImportTarget::existing($linkedObject));
        }

        return $this->firstSuggestion($entry, $context)
            ?? MiroImportSuggestion::unmapped($entry, $entry->isFrame() ? MiroImportSuggestion::REASON_NO_FRAME_MATCH : MiroImportSuggestion::REASON_UNTAGGED);
    }

    private function suggestForConnector(MiroBoardEntry $connector, MiroImportContext $context): MiroImportSuggestion
    {
        if ($context->linkedObjects($connector->externalId) !== []) {
            return new MiroImportSuggestion($connector, MiroImportAction::Unchanged);
        }

        return $this->firstSuggestion($connector, $context)
            ?? MiroImportSuggestion::unmapped($connector, MiroImportSuggestion::REASON_ENDPOINT_UNMAPPED);
    }

    private function firstSuggestion(MiroBoardEntry $entry, MiroImportContext $context): ?MiroImportSuggestion
    {
        foreach ($this->rules as $rule) {
            $suggestion = $rule->suggest($entry, $context);
            if ($suggestion instanceof MiroImportSuggestion) {
                return $suggestion;
            }
        }

        return null;
    }

    /**
     * @param list<StoryObject> $linkedObjects
     */
    private function preferredLinkedObject(MiroBoardEntry $entry, array $linkedObjects): ?StoryObject
    {
        $importableObjects = array_values(array_filter(
            $linkedObjects,
            static fn (StoryObject $storyObject): bool => MiroTypeTags::isImportable($storyObject::getTargetType()),
        ));
        if ($importableObjects === []) {
            return $linkedObjects[0] ?? null;
        }

        $taggedTypes = MiroTypeTags::typesOf($entry);
        foreach ($importableObjects as $storyObject) {
            if (in_array($storyObject::getTargetType(), $taggedTypes, true)) {
                return $storyObject;
            }
        }

        return $importableObjects[0];
    }

    /**
     * @return list<MiroMembershipHint>
     */
    private function membershipHints(MiroImportContext $context): array
    {
        $membershipHints = [];
        foreach ($context->snapshot->frames() as $frame) {
            $faction = $context->target($frame->externalId);
            if (!$faction instanceof MiroImportTarget || $faction->type !== TargetType::Faction) {
                continue;
            }

            foreach ($context->snapshot->itemsInFrame($frame->externalId) as $item) {
                if (!in_array(TargetType::Character, MiroTypeTags::typesOf($item), true)) {
                    continue;
                }
                $character = $context->target($item->externalId);
                if (!$character instanceof MiroImportTarget || $character->type !== TargetType::Character) {
                    continue;
                }

                $membershipHints[] = new MiroMembershipHint(
                    characterEntry: $item,
                    character: $character,
                    faction: $faction,
                    alreadyMember: $character->storyObject instanceof Character
                        && $faction->storyObject instanceof Faction
                        && $character->storyObject->belongsToFaction($faction->storyObject),
                );
            }
        }

        return $membershipHints;
    }
}
