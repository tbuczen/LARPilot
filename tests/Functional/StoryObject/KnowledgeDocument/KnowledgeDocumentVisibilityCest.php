<?php

declare(strict_types=1);

namespace Tests\Functional\StoryObject\KnowledgeDocument;

use App\Domain\StoryObject\Entity\KnowledgeDocument;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\Factory\StoryObject\FactionFactory;
use Tests\Support\Factory\StoryObject\KnowledgeDocumentFactory;
use Tests\Support\FunctionalTester;

/**
 * Tests for KnowledgeDocument visibility logic
 */
class KnowledgeDocumentVisibilityCest
{
    // ========================================================================
    // Public Document Tests
    // ========================================================================

    public function publicDocumentIsVisibleToAllCharacters(FunctionalTester $I): void
    {
        $I->wantTo('verify that public documents are visible to all characters');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        $character = CharacterFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->public()
            ->create()
            ->_real();

        $I->assertTrue(
            $document->isVisibleToCharacter($character),
            'Public documents should be visible to all characters'
        );
    }

    // ========================================================================
    // Private Document - Character Visibility Tests
    // ========================================================================

    public function privateDocumentIsNotVisibleByDefault(FunctionalTester $I): void
    {
        $I->wantTo('verify that private documents are not visible by default');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        $character = CharacterFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->private()
            ->create()
            ->_real();

        $I->assertFalse(
            $document->isVisibleToCharacter($character),
            'Private documents should not be visible by default'
        );
    }

    public function privateDocumentIsVisibleToSpecificCharacter(FunctionalTester $I): void
    {
        $I->wantTo('verify that private documents can be made visible to specific characters');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        $character = CharacterFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->private()
            ->visibleTo($character)
            ->create()
            ->_real();

        $I->assertTrue(
            $document->isVisibleToCharacter($character),
            'Document should be visible to character in visibleTo collection'
        );
    }

    public function privateDocumentIsNotVisibleToOtherCharacters(FunctionalTester $I): void
    {
        $I->wantTo('verify that private documents are not visible to non-authorized characters');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        $allowedCharacter = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Allowed Character')
            ->create()
            ->_real();

        $otherCharacter = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Other Character')
            ->create()
            ->_real();

        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->private()
            ->visibleTo($allowedCharacter)
            ->create()
            ->_real();

        $I->assertFalse(
            $document->isVisibleToCharacter($otherCharacter),
            'Document should not be visible to characters not in visibleTo collection'
        );
    }

    // ========================================================================
    // Private Document - Faction Visibility Tests
    // ========================================================================

    public function privateDocumentIsVisibleToFactionMembers(FunctionalTester $I): void
    {
        $I->wantTo('verify that private documents are visible to faction members');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        // Create faction
        $faction = FactionFactory::new()
            ->forLarp($larp)
            ->withTitle('Secret Society')
            ->create()
            ->_real();

        // Create character and add to faction
        $character = CharacterFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        $faction->addMember($character);

        // Persist faction changes
        $I->getEntityManager()->persist($faction);
        $I->getEntityManager()->flush();

        // Create document visible to faction
        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->private()
            ->visibleTo($faction)
            ->create()
            ->_real();

        $I->assertTrue(
            $document->isVisibleToCharacter($character),
            'Document should be visible to faction members'
        );
    }

    public function privateDocumentIsNotVisibleToNonFactionMembers(FunctionalTester $I): void
    {
        $I->wantTo('verify that faction documents are not visible to non-members');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        // Create faction
        $faction = FactionFactory::new()
            ->forLarp($larp)
            ->withTitle('Secret Society')
            ->create()
            ->_real();

        // Create character in faction
        $factionMember = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Faction Member')
            ->create()
            ->_real();

        $faction->addMember($factionMember);
        $I->getEntityManager()->persist($faction);
        $I->getEntityManager()->flush();

        // Create character NOT in faction
        $outsider = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Outsider')
            ->create()
            ->_real();

        // Create document visible to faction
        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->private()
            ->visibleTo($faction)
            ->create()
            ->_real();

        $I->assertFalse(
            $document->isVisibleToCharacter($outsider),
            'Document should not be visible to non-faction members'
        );
    }

    // ========================================================================
    // Combined Visibility Tests
    // ========================================================================

    public function documentVisibleToBothCharacterAndFaction(FunctionalTester $I): void
    {
        $I->wantTo('verify documents can be visible to both specific characters and factions');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        // Create faction
        $faction = FactionFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        // Create character in faction
        $factionMember = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Faction Member')
            ->create()
            ->_real();

        $faction->addMember($factionMember);
        $I->getEntityManager()->persist($faction);
        $I->getEntityManager()->flush();

        // Create character NOT in faction, but directly granted access
        $directAccessCharacter = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Direct Access Character')
            ->create()
            ->_real();

        // Create document visible to both faction and specific character
        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->private()
            ->visibleTo($faction)
            ->visibleTo($directAccessCharacter)
            ->create()
            ->_real();

        $I->assertTrue(
            $document->isVisibleToCharacter($factionMember),
            'Document should be visible to faction members'
        );

        $I->assertTrue(
            $document->isVisibleToCharacter($directAccessCharacter),
            'Document should be visible to characters with direct access'
        );
    }

    public function documentVisibleToMultipleFactions(FunctionalTester $I): void
    {
        $I->wantTo('verify documents can be visible to multiple factions');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        // Create two factions
        $faction1 = FactionFactory::new()
            ->forLarp($larp)
            ->withTitle('Faction 1')
            ->create()
            ->_real();

        $faction2 = FactionFactory::new()
            ->forLarp($larp)
            ->withTitle('Faction 2')
            ->create()
            ->_real();

        // Create characters for each faction
        $char1 = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Character 1')
            ->create()
            ->_real();

        $char2 = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Character 2')
            ->create()
            ->_real();

        $faction1->addMember($char1);
        $faction2->addMember($char2);
        $I->getEntityManager()->persist($faction1);
        $I->getEntityManager()->persist($faction2);
        $I->getEntityManager()->flush();

        // Create document visible to both factions
        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->private()
            ->visibleTo($faction1)
            ->visibleTo($faction2)
            ->create()
            ->_real();

        $I->assertTrue(
            $document->isVisibleToCharacter($char1),
            'Document should be visible to first faction members'
        );

        $I->assertTrue(
            $document->isVisibleToCharacter($char2),
            'Document should be visible to second faction members'
        );
    }

    // ========================================================================
    // Repository Tests
    // ========================================================================

    public function repositoryFindsDocumentsVisibleToCharacter(FunctionalTester $I): void
    {
        $I->wantTo('verify repository can find all documents visible to a character');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        $character = CharacterFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        // Create public document
        $publicDoc = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->withTitle('Public Document')
            ->public()
            ->create()
            ->_real();

        // Create private document visible to character
        $privateDoc = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->withTitle('Private Document')
            ->private()
            ->visibleTo($character)
            ->create()
            ->_real();

        // Create private document NOT visible to character
        $hiddenDoc = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->withTitle('Hidden Document')
            ->private()
            ->create()
            ->_real();

        // Get repository
        $repository = $I->getEntityManager()->getRepository(KnowledgeDocument::class);

        // Find documents visible to character
        $visibleDocs = $repository->findVisibleToCharacter($character);

        $I->assertCount(2, $visibleDocs, 'Should find 2 visible documents (public + private with access)');

        $titles = array_map(fn ($doc) => $doc->getTitle(), $visibleDocs);
        $I->assertContains('Public Document', $titles);
        $I->assertContains('Private Document', $titles);
        $I->assertNotContains('Hidden Document', $titles);
    }

    // ========================================================================
    // Polymorphic Ownership Tests - Character Documents
    // ========================================================================

    public function characterOwnedDocumentIsVisibleToOwner(FunctionalTester $I): void
    {
        $I->wantTo('verify that character-owned documents are visible to the owner');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        $character = CharacterFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->ownedBy($character)
            ->private()
            ->create()
            ->_real();

        $I->assertTrue(
            $document->isVisibleToCharacter($character),
            'Character-owned documents should be visible to the owner'
        );
    }

    public function characterOwnedDocumentIsNotVisibleToOthers(FunctionalTester $I): void
    {
        $I->wantTo('verify that character-owned documents are not visible to other characters by default');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        $owner = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Owner')
            ->create()
            ->_real();

        $other = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Other')
            ->create()
            ->_real();

        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->ownedBy($owner)
            ->private()
            ->create()
            ->_real();

        $I->assertFalse(
            $document->isVisibleToCharacter($other),
            'Character-owned documents should not be visible to other characters'
        );
    }

    public function repositoryFindsDocumentsByOwnerCharacter(FunctionalTester $I): void
    {
        $I->wantTo('verify repository can find documents owned by a specific character');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        $character = CharacterFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        $otherCharacter = CharacterFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        // Create documents owned by first character
        KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->ownedBy($character)
            ->withTitle('Character Doc 1')
            ->create();

        KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->ownedBy($character)
            ->withTitle('Character Doc 2')
            ->create();

        // Create document owned by other character
        KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->ownedBy($otherCharacter)
            ->withTitle('Other Character Doc')
            ->create();

        $repository = $I->getEntityManager()->getRepository(KnowledgeDocument::class);
        $characterDocs = $repository->findByOwner($character);

        $I->assertCount(2, $characterDocs, 'Should find 2 documents owned by the character');

        $titles = array_map(fn ($doc) => $doc->getTitle(), $characterDocs);
        $I->assertContains('Character Doc 1', $titles);
        $I->assertContains('Character Doc 2', $titles);
        $I->assertNotContains('Other Character Doc', $titles);
    }

    // ========================================================================
    // Polymorphic Ownership Tests - Faction Documents
    // ========================================================================

    public function factionOwnedDocumentIsVisibleToMembers(FunctionalTester $I): void
    {
        $I->wantTo('verify that faction-owned documents are visible to faction members');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        $faction = FactionFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        $member = CharacterFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        $faction->addMember($member);
        $I->getEntityManager()->persist($faction);
        $I->getEntityManager()->flush();

        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->ownedBy($faction)
            ->private()
            ->create()
            ->_real();

        $I->assertTrue(
            $document->isVisibleToCharacter($member),
            'Faction-owned documents should be visible to faction members'
        );
    }

    public function factionOwnedDocumentIsNotVisibleToNonMembers(FunctionalTester $I): void
    {
        $I->wantTo('verify that faction-owned documents are not visible to non-members');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        $faction = FactionFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        $member = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Member')
            ->create()
            ->_real();

        $nonMember = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Non-Member')
            ->create()
            ->_real();

        $faction->addMember($member);
        $I->getEntityManager()->persist($faction);
        $I->getEntityManager()->flush();

        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->ownedBy($faction)
            ->private()
            ->create()
            ->_real();

        $I->assertFalse(
            $document->isVisibleToCharacter($nonMember),
            'Faction-owned documents should not be visible to non-members'
        );
    }

    public function repositoryFindsDocumentsByOwnerFaction(FunctionalTester $I): void
    {
        $I->wantTo('verify repository can find documents owned by a specific faction');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        $faction1 = FactionFactory::new()
            ->forLarp($larp)
            ->withTitle('Faction 1')
            ->create()
            ->_real();

        $faction2 = FactionFactory::new()
            ->forLarp($larp)
            ->withTitle('Faction 2')
            ->create()
            ->_real();

        // Create documents owned by first faction
        KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->ownedBy($faction1)
            ->withTitle('Faction 1 Doc 1')
            ->create();

        KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->ownedBy($faction1)
            ->withTitle('Faction 1 Doc 2')
            ->create();

        // Create document owned by second faction
        KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->ownedBy($faction2)
            ->withTitle('Faction 2 Doc')
            ->create();

        $repository = $I->getEntityManager()->getRepository(KnowledgeDocument::class);
        $faction1Docs = $repository->findByOwner($faction1);

        $I->assertCount(2, $faction1Docs, 'Should find 2 documents owned by faction 1');

        $titles = array_map(fn ($doc) => $doc->getTitle(), $faction1Docs);
        $I->assertContains('Faction 1 Doc 1', $titles);
        $I->assertContains('Faction 1 Doc 2', $titles);
        $I->assertNotContains('Faction 2 Doc', $titles);
    }

    // ========================================================================
    // Polymorphic Ownership Tests - LARP Documents
    // ========================================================================

    public function larpDocumentsAreFoundByRepository(FunctionalTester $I): void
    {
        $I->wantTo('verify repository can find LARP-wide documents (documents without specific owners)');

        $creator = UserFactory::new()->approved()->create();
        $larp = LarpFactory::createDraftLarp($creator);

        $character = CharacterFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        // Create LARP-wide documents (no owner specified)
        KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->withTitle('LARP Doc 1')
            ->create();

        KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->withTitle('LARP Doc 2')
            ->create();

        // Create character-owned document
        KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->ownedBy($character)
            ->withTitle('Character Doc')
            ->create();

        $repository = $I->getEntityManager()->getRepository(KnowledgeDocument::class);
        $larpDocs = $repository->findLarpDocuments($larp->_real());

        $I->assertCount(2, $larpDocs, 'Should find only LARP-wide documents (without specific owners)');

        $titles = array_map(fn ($doc) => $doc->getTitle(), $larpDocs);
        $I->assertContains('LARP Doc 1', $titles);
        $I->assertContains('LARP Doc 2', $titles);
        $I->assertNotContains('Character Doc', $titles);
    }
}
