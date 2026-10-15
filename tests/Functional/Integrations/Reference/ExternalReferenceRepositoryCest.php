<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Reference;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Integrations\ExternalItemFactory;
use Tests\Support\Factory\Integrations\ExternalReferenceFactory;
use Tests\Support\Factory\Integrations\ExternalSourceFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\Factory\StoryObject\ThreadFactory;
use Tests\Support\FunctionalTester;

class ExternalReferenceRepositoryCest
{
    public function findForStoryObjectReturnsOnlyItsReferences(FunctionalTester $I): void
    {
        $character = CharacterFactory::new()->create();
        $otherCharacter = CharacterFactory::new(['larp' => $character->getLarp()])->create();
        $characterReferences = ExternalReferenceFactory::new(['storyObject' => $character])->many(2)->create();
        ExternalReferenceFactory::new(['storyObject' => $otherCharacter])->create();

        $references = $this->grabReferenceRepository($I)->findForStoryObject($character->_real());

        $I->assertEqualsCanonicalizing(
            $this->referenceIds(array_map(static fn ($reference) => $reference->_real(), $characterReferences)),
            $this->referenceIds($references),
        );
    }

    public function findForLarpReturnsReferencesOfAllStoryObjectsInLarp(FunctionalTester $I): void
    {
        $character = CharacterFactory::new()->create();
        $thread = ThreadFactory::new(['larp' => $character->getLarp(), 'createdBy' => UserFactory::new()])->create();
        $characterReference = ExternalReferenceFactory::new(['storyObject' => $character])->create();
        $threadReference = ExternalReferenceFactory::new(['storyObject' => $thread])->create();
        ExternalReferenceFactory::new()->create();

        $references = $this->grabReferenceRepository($I)->findForLarp($character->getLarp());

        $I->assertEqualsCanonicalizing(
            $this->referenceIds([$characterReference->_real(), $threadReference->_real()]),
            $this->referenceIds($references),
        );
    }

    public function findForLarpFiltersByProvider(FunctionalTester $I): void
    {
        $character = CharacterFactory::new()->create();
        $googleReference = ExternalReferenceFactory::new(['storyObject' => $character])->create();
        ExternalReferenceFactory::new([
            'storyObject' => $character,
            'provider' => LarpIntegrationProvider::Facebook,
        ])->create();

        $references = $this->grabReferenceRepository($I)->findForLarp($character->getLarp(), LarpIntegrationProvider::Google);

        $I->assertSame($this->referenceIds([$googleReference->_real()]), $this->referenceIds($references));
    }

    public function findOneForStoryObjectMatchesProviderAndExternalId(FunctionalTester $I): void
    {
        $character = CharacterFactory::new()->create();
        $reference = ExternalReferenceFactory::new(['storyObject' => $character, 'externalId' => 'row-7'])->create();
        $repository = $this->grabReferenceRepository($I);

        $I->assertSame(
            $reference->getId()->toRfc4122(),
            $repository->findOneForStoryObject($character->_real(), LarpIntegrationProvider::Google, 'row-7')?->getId()->toRfc4122(),
        );
        $I->assertNull($repository->findOneForStoryObject($character->_real(), LarpIntegrationProvider::Facebook, 'row-7'));
        $I->assertNull($repository->findOneForStoryObject($character->_real(), LarpIntegrationProvider::Google, 'row-8'));
    }

    public function sourceAndItemAreFoundByExternalId(FunctionalTester $I): void
    {
        $source = ExternalSourceFactory::new(['externalId' => 'channel-1'])->create();
        $item = ExternalItemFactory::new(['source' => $source, 'externalId' => 'thread-1'])->create();

        $foundSource = $I->grabService(ExternalSourceRepository::class)->findOneByExternalId($source->getIntegration(), 'channel-1');
        $foundItem = $I->grabService(ExternalItemRepository::class)->findOneByExternalId($source->_real(), 'thread-1');

        $I->assertSame($source->getId()->toRfc4122(), $foundSource?->getId()->toRfc4122());
        $I->assertSame($item->getId()->toRfc4122(), $foundItem?->getId()->toRfc4122());
    }

    /**
     * @param ExternalReference[] $references
     * @return list<string>
     */
    private function referenceIds(array $references): array
    {
        return array_values(array_map(static fn (ExternalReference $reference): string => $reference->getId()->toRfc4122(), $references));
    }

    private function grabReferenceRepository(FunctionalTester $I): ExternalReferenceRepository
    {
        return $I->grabService(ExternalReferenceRepository::class);
    }
}
