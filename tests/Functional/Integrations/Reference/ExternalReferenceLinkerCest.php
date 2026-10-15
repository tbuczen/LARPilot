<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Reference;

use App\Domain\Integrations\DTO\ExternalReferenceLink;
use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Entity\Enum\ExternalItemReviewStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Event\ExternalReferenceLinked;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;
use App\Domain\Integrations\Service\ExternalReferenceLinker;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Integrations\ExternalItemFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;

class ExternalReferenceLinkerCest
{
    /** @var list<ExternalReferenceLinked> */
    private array $dispatchedEvents = [];

    public function _before(FunctionalTester $I): void
    {
        $this->dispatchedEvents = [];
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $I->grabService('event_dispatcher');
        $eventDispatcher->addListener(ExternalReferenceLinked::class, function (ExternalReferenceLinked $event): void {
            $this->dispatchedEvents[] = $event;
        });
    }

    public function linkCreatesReferenceScopedToTheStoryObjectLarp(FunctionalTester $I): void
    {
        $character = CharacterFactory::new()->create()->_real();
        $linker = $this->grabLinker($I);

        $reference = $linker->link($character, $this->buildLink('doc-1', '<p>Some <b>excerpt</b></p>'));

        $I->assertSame($character, $reference->getStoryObject());
        $I->assertSame($character->getLarp(), $reference->getLarp());
        $I->assertSame('Some excerpt', $reference->getExcerpt());
        $I->assertSame(ReferenceType::Document, $reference->getReferenceType());
        $I->assertSame($character->getCreatedBy(), $reference->getCreatedBy());
    }

    public function linkDispatchesLinkedEvent(FunctionalTester $I): void
    {
        $character = CharacterFactory::new()->create()->_real();

        $reference = $this->grabLinker($I)->link($character, $this->buildLink('doc-1'));

        $I->assertCount(1, $this->dispatchedEvents);
        $I->assertSame($reference, $this->dispatchedEvents[0]->getReference());
    }

    public function duplicateLinkIsNoOp(FunctionalTester $I): void
    {
        $character = CharacterFactory::new()->create()->_real();
        $linker = $this->grabLinker($I);

        $firstReference = $linker->link($character, $this->buildLink('doc-1'));
        $secondReference = $linker->link($character, $this->buildLink('doc-1'));

        $I->assertSame($firstReference->getId()->toRfc4122(), $secondReference->getId()->toRfc4122());
        $I->assertCount(1, $this->grabReferenceRepository($I)->findForStoryObject($character));
        $I->assertCount(1, $this->dispatchedEvents);
    }

    public function sameExternalIdCanBeLinkedToDifferentStoryObjects(FunctionalTester $I): void
    {
        $firstCharacter = CharacterFactory::new()->create()->_real();
        $secondCharacter = CharacterFactory::new()->create()->_real();
        $linker = $this->grabLinker($I);

        $firstReference = $linker->link($firstCharacter, $this->buildLink('doc-1'));
        $secondReference = $linker->link($secondCharacter, $this->buildLink('doc-1'));

        $I->assertNotSame($firstReference->getId()->toRfc4122(), $secondReference->getId()->toRfc4122());
        $I->assertCount(2, $this->dispatchedEvents);
    }

    public function linkItemCopiesItemDataAndMarksItLinked(FunctionalTester $I): void
    {
        $character = CharacterFactory::new()->create()->_real();
        $item = ExternalItemFactory::new([
            'kind' => ExternalItemKind::Frame,
            'excerpt' => 'Frame excerpt',
        ])->create()->_real();

        $reference = $this->grabLinker($I)->linkItem($character, $item, ReferenceRole::Primary);

        $I->assertSame($item, $reference->getExternalItem());
        $I->assertSame($item->getSource(), $reference->getSource());
        $I->assertSame(ReferenceType::BoardFrame, $reference->getReferenceType());
        $I->assertSame(ReferenceRole::Primary, $reference->getRole());
        $I->assertSame($item->getExternalId(), $reference->getExternalId());
        $I->assertSame('Frame excerpt', $reference->getExcerpt());
        $I->assertEquals($item->getExternalUpdatedAt(), $reference->getExternalUpdatedAt());
        $I->assertSame(ExternalItemReviewStatus::Linked, $item->getReviewStatus());
        $I->assertCount(1, $this->dispatchedEvents);
    }

    public function linkingTheSameItemTwiceIsNoOp(FunctionalTester $I): void
    {
        $character = CharacterFactory::new()->create()->_real();
        $item = ExternalItemFactory::new()->create()->_real();
        $linker = $this->grabLinker($I);

        $linker->linkItem($character, $item);
        $linker->linkItem($character, $item);

        $I->assertCount(1, $this->grabReferenceRepository($I)->findForStoryObject($character));
        $I->assertCount(1, $this->dispatchedEvents);
    }

    public function unlinkRemovesReferenceAndIsIdempotent(FunctionalTester $I): void
    {
        $character = CharacterFactory::new()->create()->_real();
        $linker = $this->grabLinker($I);
        $linker->link($character, $this->buildLink('doc-1'));

        $I->assertTrue($linker->unlink($character, LarpIntegrationProvider::Google, 'doc-1'));
        $I->assertFalse($linker->unlink($character, LarpIntegrationProvider::Google, 'doc-1'));
        $I->assertCount(0, $this->grabReferenceRepository($I)->findForStoryObject($character));
    }

    public function unlinkingLastReferenceReturnsItemToUnreviewed(FunctionalTester $I): void
    {
        $firstCharacter = CharacterFactory::new()->create()->_real();
        $secondCharacter = CharacterFactory::new()->create()->_real();
        $item = ExternalItemFactory::new()->create()->_real();
        $linker = $this->grabLinker($I);
        $linker->linkItem($firstCharacter, $item);
        $linker->linkItem($secondCharacter, $item);

        $linker->unlinkItem($firstCharacter, $item);
        $I->assertSame(ExternalItemReviewStatus::Linked, $item->getReviewStatus());

        $linker->unlinkItem($secondCharacter, $item);
        $I->assertSame(ExternalItemReviewStatus::Unreviewed, $item->getReviewStatus());
    }

    public function explicitLinkerUserBecomesCreator(FunctionalTester $I): void
    {
        $character = CharacterFactory::new()->create()->_real();
        $organizer = UserFactory::createApprovedUser();

        $reference = $this->grabLinker($I)->link($character, new ExternalReferenceLink(
            provider: LarpIntegrationProvider::Google,
            externalId: 'doc-2',
            referenceType: ReferenceType::Document,
            linkedBy: $organizer,
        ));

        $I->assertSame($organizer, $reference->getCreatedBy());
    }

    private function buildLink(string $externalId, ?string $excerpt = null): ExternalReferenceLink
    {
        return new ExternalReferenceLink(
            provider: LarpIntegrationProvider::Google,
            externalId: $externalId,
            referenceType: ReferenceType::Document,
            name: 'Character sheet',
            url: 'https://docs.example.test/' . $externalId,
            excerpt: $excerpt,
        );
    }

    private function grabLinker(FunctionalTester $I): ExternalReferenceLinker
    {
        return new ExternalReferenceLinker(
            $this->grabReferenceRepository($I),
            $I->grabService('doctrine.orm.entity_manager'),
            $I->grabService('event_dispatcher'),
        );
    }

    private function grabReferenceRepository(FunctionalTester $I): ExternalReferenceRepository
    {
        return $I->grabService(ExternalReferenceRepository::class);
    }
}
