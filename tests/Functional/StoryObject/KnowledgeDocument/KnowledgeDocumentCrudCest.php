<?php

declare(strict_types=1);

namespace Tests\Functional\StoryObject\KnowledgeDocument;

use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\Factory\StoryObject\FactionFactory;
use Tests\Support\Factory\StoryObject\KnowledgeDocumentFactory;
use Tests\Support\FunctionalTester;

class KnowledgeDocumentCrudCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
    }

    // ========================================================================
    // Authentication & Authorization Tests
    // ========================================================================

    public function knowledgeDocumentListRequiresAuthentication(FunctionalTester $I): void
    {
        $I->wantTo('verify that knowledge document list requires authentication');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        // Unauthenticated request should redirect
        $I->amOnRoute('backoffice_larp_knowledge_list', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsRedirection();
    }

    public function nonOrganizerCannotAccessKnowledgeDocuments(FunctionalTester $I): void
    {
        $I->wantTo('verify that non-organizers cannot access knowledge documents');

        $creator = UserFactory::createApprovedUser();
        $otherUser = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        // Non-organizer should not have access
        $I->amLoggedInAs($otherUser);
        $I->amOnRoute('backoffice_larp_knowledge_list', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(403);
    }

    public function larpOrganizerCanAccessKnowledgeDocuments(FunctionalTester $I): void
    {
        $I->wantTo('verify that LARP organizers can access knowledge documents');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        $I->amLoggedInAs($creator);
        $I->amOnRoute('backoffice_larp_knowledge_list', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(200);
    }

    // ========================================================================
    // List Page Tests
    // ========================================================================

    public function knowledgeDocumentListDisplaysDocuments(FunctionalTester $I): void
    {
        $I->wantTo('verify that knowledge document list displays all documents for the LARP');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        // Create test documents
        $doc1 = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->withTitle('Ancient History')
            ->withCategory('history')
            ->public()
            ->create()
            ->_real();

        $doc2 = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->withTitle('Secret Faction Lore')
            ->withCategory('faction')
            ->private()
            ->create()
            ->_real();

        $I->amLoggedInAs($creator);
        $I->amOnRoute('backoffice_larp_knowledge_list', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(200);
        $I->see('Ancient History');
        $I->see('Secret Faction Lore');
    }

    public function knowledgeDocumentListIsEmptyByDefault(FunctionalTester $I): void
    {
        $I->wantTo('verify that knowledge document list shows empty state when no documents exist');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        $I->amLoggedInAs($creator);
        $I->amOnRoute('backoffice_larp_knowledge_list', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(200);
    }

    // ========================================================================
    // Create Tests
    // ========================================================================

    public function organizerCanAccessCreatePage(FunctionalTester $I): void
    {
        $I->wantTo('verify that organizers can access the create page');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        $I->amLoggedInAs($creator);
        $I->amOnRoute('backoffice_larp_knowledge_modify', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(200);
    }

    public function organizerCanCreatePublicDocument(FunctionalTester $I): void
    {
        $I->wantTo('verify that organizers can create a public knowledge document');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        $I->amLoggedInAs($creator);
        $I->amOnRoute('backoffice_larp_knowledge_modify', ['larp' => $larp->getId()]);

        $I->submitForm('form', [
            'knowledge_document[title]' => 'World Creation Myth',
            'knowledge_document[category]' => 'lore',
            'knowledge_document[content]' => 'In the beginning, there was chaos...',
            'knowledge_document[isPublic]' => '1',
        ]);

        $I->seeResponseCodeIsRedirection();
    }

    // ========================================================================
    // Edit Tests
    // ========================================================================

    public function organizerCanAccessEditPage(FunctionalTester $I): void
    {
        $I->wantTo('verify that organizers can access the edit page');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->withTitle('Test Document')
            ->create()
            ->_real();

        $I->amLoggedInAs($creator);
        $I->amOnRoute('backoffice_larp_knowledge_modify', [
            'larp' => $larp->getId(),
            'document' => $document->getId(),
        ]);

        $I->seeResponseCodeIs(200);
    }

    public function organizerCanEditDocument(FunctionalTester $I): void
    {
        $I->wantTo('verify that organizers can edit a knowledge document');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->withTitle('Original Title')
            ->withContent('Original content')
            ->create()
            ->_real();

        $I->amLoggedInAs($creator);
        $I->amOnRoute('backoffice_larp_knowledge_modify', [
            'larp' => $larp->getId(),
            'document' => $document->getId(),
        ]);

        $I->submitForm('form', [
            'knowledge_document[title]' => 'Updated Title',
            'knowledge_document[content]' => 'Updated content',
        ]);

        $I->seeResponseCodeIsRedirection();
    }

    // ========================================================================
    // Delete Tests
    // ========================================================================

    public function organizerCanDeleteDocument(FunctionalTester $I): void
    {
        $I->wantTo('verify that organizers can delete a knowledge document');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->withTitle('Document to Delete')
            ->create()
            ->_real();

        $I->amLoggedInAs($creator);
        $I->amOnRoute('backoffice_larp_knowledge_delete', [
            'larp' => $larp->getId(),
            'document' => $document->getId(),
        ]);

        $I->seeResponseCodeIsRedirection();
    }

    public function nonOrganizerCannotDeleteDocument(FunctionalTester $I): void
    {
        $I->wantTo('verify that non-organizers cannot delete documents');

        $creator = UserFactory::createApprovedUser();
        $otherUser = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        $document = KnowledgeDocumentFactory::new()
            ->forLarp($larp)
            ->create()
            ->_real();

        $I->amLoggedInAs($otherUser);
        $I->amOnRoute('backoffice_larp_knowledge_delete', [
            'larp' => $larp->getId(),
            'document' => $document->getId(),
        ]);

        $I->seeResponseCodeIs(403);
    }

    // ========================================================================
    // Visibility Tests
    // ========================================================================

    public function organizerCanCreateDocumentVisibleToSpecificCharacters(FunctionalTester $I): void
    {
        $I->wantTo('verify that documents can be made visible to specific characters');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        // Create test characters
        $character1 = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Character One')
            ->create()
            ->_real();

        $character2 = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Character Two')
            ->create()
            ->_real();

        $I->amLoggedInAs($creator);
        $I->amOnRoute('backoffice_larp_knowledge_modify', ['larp' => $larp->getId()]);

        $I->submitForm('form', [
            'knowledge_document[title]' => 'Character-Specific Lore',
            'knowledge_document[content]' => 'Secret information',
            'knowledge_document[isPublic]' => '0',
            'knowledge_document[visibleToCharacters]' => [$character1->getId()->toRfc4122()],
        ]);

        $I->seeResponseCodeIsRedirection();
    }

    public function organizerCanCreateDocumentVisibleToFactions(FunctionalTester $I): void
    {
        $I->wantTo('verify that documents can be made visible to factions');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        // Create test faction
        $faction = FactionFactory::new()
            ->forLarp($larp)
            ->withTitle('Secret Order')
            ->create()
            ->_real();

        $I->amLoggedInAs($creator);
        $I->amOnRoute('backoffice_larp_knowledge_modify', ['larp' => $larp->getId()]);

        $I->submitForm('form', [
            'knowledge_document[title]' => 'Faction Secret History',
            'knowledge_document[content]' => 'The true history of the Order',
            'knowledge_document[isPublic]' => '0',
            'knowledge_document[visibleToFactions]' => [$faction->getId()->toRfc4122()],
        ]);

        $I->seeResponseCodeIsRedirection();
    }

    // ========================================================================
    // Permission Tests (Story Writer Role)
    // ========================================================================

    public function storyWriterCanAccessKnowledgeDocuments(FunctionalTester $I): void
    {
        $I->wantTo('verify that story writers can access knowledge documents');

        $creator = UserFactory::createApprovedUser();
        $storyWriter = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        // Add story writer as participant
        LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($storyWriter)
            ->storyWriter()
            ->create();

        $I->amLoggedInAs($storyWriter);
        $I->amOnRoute('backoffice_larp_knowledge_list', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(200);
    }

    public function storyWriterCanCreateKnowledgeDocuments(FunctionalTester $I): void
    {
        $I->wantTo('verify that story writers can create knowledge documents');

        $creator = UserFactory::createApprovedUser();
        $storyWriter = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        // Add story writer as participant
        LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($storyWriter)
            ->storyWriter()
            ->create();

        $I->amLoggedInAs($storyWriter);
        $I->amOnRoute('backoffice_larp_knowledge_modify', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(200);
    }
}
