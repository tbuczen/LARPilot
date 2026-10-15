<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Miro\Import;

use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Miro\Import\MiroImportAction;
use App\Domain\Integrations\Miro\Import\MiroImportConfidence;
use App\Domain\Integrations\Miro\Import\MiroImportContext;
use App\Domain\Integrations\Miro\Import\MiroImportPlan;
use App\Domain\Integrations\Miro\Import\MiroImportPlanner;
use App\Domain\Integrations\Miro\Import\MiroImportSuggestion;
use App\Domain\Integrations\Miro\Import\MiroStoryObjectIndex;
use App\Domain\Integrations\Miro\Import\Rule\ConnectorRelationRule;
use App\Domain\Integrations\Miro\Import\Rule\ExactTitleMatchRule;
use App\Domain\Integrations\Miro\Import\Rule\FrameTitleRule;
use App\Domain\Integrations\Miro\Import\Rule\TypeTaggedItemRule;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardSnapshot;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Enum\RelationType;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Entity\Item;
use App\Domain\StoryObject\Entity\Place;
use App\Domain\StoryObject\Entity\Relation;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Repository\RelationRepository;
use App\Domain\StoryObject\Repository\StoryObjectRepository;
use Codeception\Test\Unit;

class MiroImportPlannerTest extends Unit
{
    public function testTypeTaggedItemLinksOnNormalizedTitleOrIsCreated(): void
    {
        $captain = $this->storyObject(Character::class, 'Captain Mira Valé');
        $plan = $this->plan([
            $this->item('1', 'card', 'captain mira vale', ['Character']),
            $this->item('2', 'sticky_note', 'Old Lighthouse', ['place']),
            $this->item('3', 'app_card', 'Mira', ['Character', 'Faction']),
        ], [$captain]);

        $this->assertSame(MiroImportAction::Link, $this->suggestion($plan, '1')->action);
        $this->assertSame(MiroImportConfidence::High, $this->suggestion($plan, '1')->confidence);
        $this->assertSame($captain, $this->suggestion($plan, '1')->target?->storyObject);
        $this->assertSame(MiroImportAction::Create, $this->suggestion($plan, '2')->action);
        $this->assertSame(TargetType::Place, $this->suggestion($plan, '2')->target?->type);
        $this->assertSame('Old Lighthouse', $this->suggestion($plan, '2')->target?->title);
        $this->assertSame(MiroImportAction::Unmapped, $this->suggestion($plan, '3')->action);
        $this->assertSame(MiroImportSuggestion::REASON_AMBIGUOUS_TAGS, $this->suggestion($plan, '3')->reason);
    }

    public function testTaggedTitleOfAnotherTypeIsCreatedNotLinked(): void
    {
        $plan = $this->plan([$this->item('1', 'card', 'Raven', ['Character'])], [$this->storyObject(Item::class, 'Raven')]);

        $this->assertSame(MiroImportAction::Create, $this->suggestion($plan, '1')->action);
    }

    public function testUntaggedItemLinksOnlyOnExactTitleWithLowConfidence(): void
    {
        $plan = $this->plan([
            $this->item('1', 'shape', 'Brother Tomas'),
            $this->item('2', 'sticky_note', 'brother tomas'),
            $this->item('3', 'text', 'Act II'),
        ], [$this->storyObject(Character::class, 'Brother Tomas')]);

        $this->assertSame(MiroImportAction::Link, $this->suggestion($plan, '1')->action);
        $this->assertSame(MiroImportConfidence::Low, $this->suggestion($plan, '1')->confidence);
        $this->assertSame(MiroImportAction::Unmapped, $this->suggestion($plan, '2')->action);
        $this->assertSame(MiroImportSuggestion::REASON_UNTAGGED, $this->suggestion($plan, '3')->reason);
    }

    public function testAmbiguousTitleIsNeverGuessed(): void
    {
        $plan = $this->plan([
            $this->item('1', 'card', 'Twin', ['Character']),
            $this->item('2', 'shape', 'Twin'),
        ], [$this->storyObject(Character::class, 'Twin'), $this->storyObject(Character::class, 'twin'), $this->storyObject(Item::class, 'Twin')]);

        $this->assertSame(MiroImportSuggestion::REASON_AMBIGUOUS_MATCH, $this->suggestion($plan, '1')->reason);
        $this->assertSame(MiroImportSuggestion::REASON_AMBIGUOUS_MATCH, $this->suggestion($plan, '2')->reason);
    }

    public function testFrameLinksToFactionThreadOrPlaceAndShowsTaggedMembers(): void
    {
        $faction = $this->storyObject(Faction::class, 'House Morwen');
        $member = $this->storyObject(Character::class, 'Lady Morwen');
        $faction->addMember($member);
        $plan = $this->plan([
            $this->frame('f1', 'House Morwen'),
            $this->frame('f2', 'Harbour'),
            $this->frame('f3', 'Act I'),
            $this->item('1', 'sticky_note', 'Lady Morwen', ['Character'], 'f1'),
            $this->item('2', 'sticky_note', 'Cousin Elric', ['Character'], 'f1'),
            $this->item('3', 'sticky_note', 'Old chapel', [], 'f1'),
        ], [$faction, $member, $this->storyObject(Place::class, 'Harbour')]);

        $this->assertSame(MiroImportAction::Link, $this->suggestion($plan, 'f1')->action);
        $this->assertSame($faction, $this->suggestion($plan, 'f1')->target?->storyObject);
        $this->assertSame(MiroImportAction::Link, $this->suggestion($plan, 'f2')->action);
        $this->assertSame(MiroImportSuggestion::REASON_NO_FRAME_MATCH, $this->suggestion($plan, 'f3')->reason);

        $this->assertCount(2, $plan->membershipHints);
        $this->assertSame('1', $plan->membershipHints[0]->characterEntry->externalId);
        $this->assertTrue($plan->membershipHints[0]->alreadyMember);
        $this->assertSame('2', $plan->membershipHints[1]->characterEntry->externalId);
        $this->assertFalse($plan->membershipHints[1]->alreadyMember);
    }

    public function testConnectorCaptionsBecomeValidatedRelations(): void
    {
        $captain = $this->storyObject(Character::class, 'Captain Mira Vale');
        $plan = $this->plan([
            $this->item('1', 'card', 'Captain Mira Vale', ['Character']),
            $this->item('2', 'card', 'Lady Morwen', ['Character']),
            $this->item('3', 'card', 'Old Lighthouse', ['Place']),
            $this->item('4', 'shape', 'Loose note'),
            $this->connector('c1', '1', '2', 'Rywal'),
            $this->connector('c2', '1', '2', 'suspects'),
            $this->connector('c3', '1', '3', 'friend'),
            $this->connector('c4', '4', '2', 'ally'),
            $this->connector('c5', '1', '2', ''),
        ], [$captain]);

        $relation = $this->suggestion($plan, 'c1');
        $this->assertSame(MiroImportAction::CreateRelation, $relation->action);
        $this->assertSame(RelationType::Enemy, $relation->relationType);
        $this->assertSame($captain, $relation->from?->storyObject);
        $this->assertTrue($relation->to?->isPlanned());
        $this->assertSame(MiroImportAction::NeedsType, $this->suggestion($plan, 'c2')->action);
        $this->assertSame(MiroImportAction::Incompatible, $this->suggestion($plan, 'c3')->action);
        $this->assertSame(MiroImportSuggestion::REASON_ENDPOINT_UNMAPPED, $this->suggestion($plan, 'c4')->reason);
        $this->assertSame(MiroImportAction::NeedsType, $this->suggestion($plan, 'c5')->action);
    }

    public function testAlreadyLinkedElementsAndExistingRelationsAreUnchanged(): void
    {
        $captain = $this->storyObject(Character::class, 'Captain');
        $morwen = $this->storyObject(Character::class, 'Morwen');
        $existingRelation = (new Relation())->setFrom($morwen)->setTo($captain)->setRelationType(RelationType::Friend);
        $plan = $this->plan([
            $this->item('1', 'card', 'Captain Renamed In Miro', ['Character']),
            $this->item('2', 'card', 'Morwen', ['Character']),
            $this->connector('c1', '1', '2', 'friends'),
            $this->connector('c2', '1', '2', 'family'),
        ], [$captain, $morwen], ['1' => [$captain]], [$existingRelation]);

        $this->assertSame(MiroImportAction::Unchanged, $this->suggestion($plan, '1')->action);
        $this->assertSame(MiroImportAction::Link, $this->suggestion($plan, '2')->action);
        $this->assertSame(MiroImportAction::Unchanged, $this->suggestion($plan, 'c1')->action);
        $this->assertSame(MiroImportAction::CreateRelation, $this->suggestion($plan, 'c2')->action);
        $this->assertSame(2, $plan->unchangedCount());
        $this->assertCount(2, $plan->changes());
    }

    public function testTokensIdentifyTheSuggestedChange(): void
    {
        $entry = $this->item('1', 'card', 'Old Lighthouse', ['Place']);
        $plan = $this->plan([$entry], []);
        $token = $this->suggestion($plan, '1')->token();

        $this->assertSame($token, $this->plan([$entry], [])->changes()[0]->token());
        $this->assertNotSame($token, MiroImportSuggestion::create($entry, TargetType::Item)->token());
        $this->assertSame($this->suggestion($plan, '1'), $plan->suggestionWithToken($token));
        $this->assertNull($plan->suggestionWithToken('forged'));
    }

    /**
     * @param list<MiroBoardEntry> $entries
     * @param list<StoryObject> $storyObjects
     * @param array<string, list<StoryObject>> $linkedObjectsByExternalId
     * @param list<Relation> $relations
     */
    private function plan(array $entries, array $storyObjects, array $linkedObjectsByExternalId = [], array $relations = []): MiroImportPlan
    {
        $planner = new MiroImportPlanner(
            [new TypeTaggedItemRule(), new FrameTitleRule(), new ExactTitleMatchRule(), new ConnectorRelationRule()],
            $this->createStub(StoryObjectRepository::class),
            $this->createStub(RelationRepository::class),
            $this->createStub(ExternalReferenceRepository::class),
        );

        return $planner->planFor(new MiroImportContext(
            larp: new Larp(),
            snapshot: new MiroBoardSnapshot('board', $entries, new \DateTimeImmutable('2026-10-14 10:00:00')),
            index: new MiroStoryObjectIndex($storyObjects),
            linkedObjectsByExternalId: $linkedObjectsByExternalId,
            existingRelations: $relations,
        ));
    }

    private function suggestion(MiroImportPlan $plan, string $externalId): MiroImportSuggestion
    {
        foreach ($plan->suggestions as $suggestion) {
            if ($suggestion->entry->externalId === $externalId) {
                return $suggestion;
            }
        }

        $this->fail(sprintf('No suggestion for "%s".', $externalId));
    }

    /**
     * @template T of StoryObject
     * @param class-string<T> $storyObjectClass
     * @return T
     */
    private function storyObject(string $storyObjectClass, string $title): StoryObject
    {
        $storyObject = new $storyObjectClass();
        $storyObject->setTitle($title);

        return $storyObject;
    }

    /**
     * @param list<string> $tags
     */
    private function item(string $externalId, string $type, string $title, array $tags = [], ?string $frameId = null): MiroBoardEntry
    {
        return new MiroBoardEntry($externalId, ExternalItemKind::BoardItem, $type, $title, $title, $tags, 'https://miro.test/' . $externalId, $frameId);
    }

    private function frame(string $externalId, string $title): MiroBoardEntry
    {
        return new MiroBoardEntry($externalId, ExternalItemKind::Frame, MiroBoardEntry::TYPE_FRAME, $title, '', [], 'https://miro.test/' . $externalId);
    }

    private function connector(string $externalId, string $startItemId, string $endItemId, string $caption): MiroBoardEntry
    {
        return new MiroBoardEntry(
            externalId: $externalId,
            kind: ExternalItemKind::Connector,
            type: MiroBoardEntry::TYPE_CONNECTOR,
            title: $caption,
            text: $caption,
            tags: [],
            url: 'https://miro.test/' . $externalId,
            startItemId: $startItemId,
            endItemId: $endItemId,
        );
    }
}
