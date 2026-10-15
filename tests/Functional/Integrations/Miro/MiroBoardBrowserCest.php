<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Miro;

use App\Domain\Account\Entity\User;
use App\Domain\Integrations\Entity\Enum\ExternalItemAvailability;
use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Entity\Enum\ExternalItemReviewStatus;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceSyncStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Message\SyncExternalSourceMessage;
use App\Domain\Integrations\MessageHandler\SyncExternalSourceHandler;
use App\Domain\Integrations\Miro\OAuth\MiroOAuthProvider;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardSnapshotProvider;
use App\Domain\Integrations\Miro\Snapshot\MiroSourceContentProvider;
use App\Domain\Integrations\Service\Capability\BoardSourceIntegrationInterface;
use App\Domain\Integrations\Service\Capability\SourceSyncIntegrationInterface;
use App\Domain\Integrations\Service\ExternalReferenceLinker;
use App\Domain\Integrations\Service\IntegrationRegistry;
use App\Domain\StoryObject\Entity\Character;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\Integrations\ExternalItemFactory;
use Tests\Support\Factory\Integrations\ExternalSourceFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Http\FixtureRouter;
use Tests\Support\Http\IntegrationFixtureRouters;
use Tests\Support\Integrations\FakeMiroOAuthGateway;

class MiroBoardBrowserCest
{
    private const BOARD_ID = IntegrationFixtureRouters::MIRO_BOARD_ID;
    private const FRAME_ID = IntegrationFixtureRouters::MIRO_FRAME_ID;
    private const SHAPE_ID = '3458764600000000011';
    private const LADY_MORWEN_ID = '3458764600000000101';
    private const SUSPECTS_CONNECTOR_ID = '3458764600000000501';
    private const LINK_FORM = 'form[name="miro_board_link"]';

    public function _before(FunctionalTester $I): void
    {
        $this->integrationsTransport($I)->reset();
    }

    public function miroProvidesBoardAndSyncCapabilities(FunctionalTester $I): void
    {
        $I->wantTo('see Miro registered for board browsing and background sync');

        /** @var IntegrationRegistry $integrationRegistry */
        $integrationRegistry = $I->grabService(IntegrationRegistry::class);

        $I->assertTrue($integrationRegistry->supports(LarpIntegrationProvider::Miro, BoardSourceIntegrationInterface::class));
        $I->assertTrue($integrationRegistry->supports(LarpIntegrationProvider::Miro, SourceSyncIntegrationInterface::class));
    }

    public function syncThroughTheHandlerStoresTheBoardSnapshot(FunctionalTester $I): void
    {
        $I->wantTo('sync a Miro board through the background sync handler and store frames, items and connectors');

        $organizer = UserFactory::createApprovedUser();
        $board = $this->createBoard(LarpFactory::createDraftLarp($organizer), $organizer);

        $this->syncHandler($I)(new SyncExternalSourceMessage($board->getId()));

        $syncedBoard = $this->reloadBoard($I, $board);
        $items = $this->itemsByExternalId($I, $syncedBoard);
        $I->assertCount(10, $items);
        $I->assertSame(ExternalItemKind::Frame, $items[self::FRAME_ID]->getKind());
        $I->assertSame('House Morwen', $items[self::FRAME_ID]->getTitle());
        $I->assertSame(ExternalItemKind::BoardItem, $items[self::LADY_MORWEN_ID]->getKind());
        $I->assertSame(self::FRAME_ID, $items[self::LADY_MORWEN_ID]->getParentExternalId());
        $I->assertSame('https://miro.com/app/board/uXjVOD6LSME=/?moveToWidget=' . self::LADY_MORWEN_ID, $items[self::LADY_MORWEN_ID]->getUrl());
        $I->assertSame(ExternalItemKind::Connector, $items[self::SUSPECTS_CONNECTOR_ID]->getKind());
        $I->assertSame('Lady Morwen — suspects → Cousin Elric', $items[self::SUSPECTS_CONNECTOR_ID]->getTitle());
        $I->assertEquals(['type' => 'sticky_note', 'tags' => ['Main plot']], $items['3458764600000000010']->getRaw());

        $I->assertSame(ExternalSourceSyncStatus::Success, $syncedBoard->getLastSyncStatus());
        $I->assertNotNull($syncedBoard->getLastSyncedAt());
        $syncCursor = $syncedBoard->getSyncCursor();
        $I->assertIsArray($syncCursor);
        $I->assertSame(10, $syncCursor['itemCount']);
        $I->assertSame(64, strlen($syncCursor['contentHash']));
        $I->assertArrayHasKey('snapshotBuiltAt', $syncCursor);
    }

    public function renamedItemIsMarkedChanged(FunctionalTester $I): void
    {
        $I->wantTo('see a renamed Miro item marked as changed while its linked story object stays untouched');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $board = $this->createBoard($larp, $organizer);
        $character = CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->create(['title' => 'Guild master']);
        $renamedItem = ExternalItemFactory::new([
            'source' => $board,
            'externalId' => self::SHAPE_ID,
            'kind' => ExternalItemKind::BoardItem,
            'title' => 'Thieves Guild',
        ])->create();
        $this->linker($I)->linkItem($character->_real(), $renamedItem->_real(), ReferenceRole::Primary, $organizer);

        $this->syncHandler($I)(new SyncExternalSourceMessage($board->getId()));
        $items = $this->itemsByExternalId($I, $this->reloadBoard($I, $board));

        $I->assertSame("Smugglers' Guild", $items[self::SHAPE_ID]->getTitle());
        $I->assertSame(ExternalItemAvailability::Changed, $items[self::SHAPE_ID]->getAvailability());
        $I->assertSame(ExternalItemAvailability::Ok, $items[self::LADY_MORWEN_ID]->getAvailability());

        $this->syncHandler($I)(new SyncExternalSourceMessage($board->getId()));
        $I->assertSame(ExternalItemAvailability::Changed, $this->itemsByExternalId($I, $this->reloadBoard($I, $board))[self::SHAPE_ID]->getAvailability());

        $storedCharacter = $I->getEntityManager()->find(Character::class, $character->getId());
        $I->assertNotNull($storedCharacter);
        $I->assertSame('Guild master', $storedCharacter->getTitle());
        $I->assertCount(1, $storedCharacter->getExternalReferences());
    }

    public function deletedItemIsMarkedMissingAndItsLinkKept(FunctionalTester $I): void
    {
        $I->wantTo('see an item removed from the Miro board marked as missing without deleting the story object or its link');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $board = $this->createBoard($larp, $organizer);
        $character = CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->create(['title' => 'Lost heir']);
        $deletedItem = ExternalItemFactory::new([
            'source' => $board,
            'externalId' => '3458764600000000999',
            'kind' => ExternalItemKind::BoardItem,
            'title' => 'The lost heir',
        ])->create();
        $this->linker($I)->linkItem($character->_real(), $deletedItem->_real(), ReferenceRole::Mention, $organizer);

        $this->syncHandler($I)(new SyncExternalSourceMessage($board->getId()));

        $items = $this->itemsByExternalId($I, $this->reloadBoard($I, $board));
        $I->assertSame(ExternalItemAvailability::Gone, $items['3458764600000000999']->getAvailability());
        $I->assertSame('The lost heir', $items['3458764600000000999']->getTitle());
        $I->assertSame(ExternalItemAvailability::Ok, $items[self::SHAPE_ID]->getAvailability());
        $I->assertNotNull($I->getEntityManager()->find(Character::class, $character->getId()));
        $I->assertCount(1, $I->getEntityManager()->getRepository(ExternalReference::class)->findBy(['externalId' => '3458764600000000999']));

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_miro_browser_show', $this->boardRoute($larp, $board));
        $I->see('The lost heir', '[data-miro-missing]');
        $I->see('Lost heir', '[data-miro-missing] [data-miro-reference]');
    }

    public function rateLimitedSyncIsRequeuedWithMiroRetryDelay(FunctionalTester $I): void
    {
        $I->wantTo('see a Miro 429 re-queue the board sync after the delay from the rate limit headers');

        $organizer = UserFactory::createApprovedUser();
        $board = $this->createBoard(LarpFactory::createDraftLarp($organizer), $organizer);

        $router = $this->miroRouter($I);
        $router->on('GET', '/boards/{board_id}/tags', 'rate_limited.json', 429, ['x-ratelimit-reset' => '45']);
        try {
            $this->syncHandler($I)(new SyncExternalSourceMessage($board->getId()));
        } finally {
            $router->on('GET', '/boards/{board_id}/tags', 'tags.json');
        }

        $sentEnvelopes = $this->integrationsTransport($I)->getSent();
        $I->assertCount(1, $sentEnvelopes);
        $I->assertTrue($board->getId()->equals($sentEnvelopes[0]->getMessage()->sourceId));
        $I->assertEquals(new DelayStamp(45000), $sentEnvelopes[0]->last(DelayStamp::class));

        $rateLimitedBoard = $this->reloadBoard($I, $board);
        $I->assertNull($rateLimitedBoard->getLastSyncStatus());
        $I->assertCount(0, $rateLimitedBoard->getItems());
    }

    public function organizerBrowsesFramesItemsAndConnectors(FunctionalTester $I): void
    {
        $I->wantTo('browse a Miro board grouped by frames with item types, tags, connectors and deep links');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $board = $this->createBoard($larp, $organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_sources', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Miro->value]);
        $browseUrl = $I->getUrl('backoffice_larp_integration_miro_browser_show', $this->boardRoute($larp, $board));
        $I->seeElement(sprintf('a.btn-outline-info[data-miro-browse-board][href="%s"]', $browseUrl));

        $I->amOnPage($browseUrl);
        $I->seeResponseCodeIsSuccessful();
        $I->see('Shadows of Ardenne — plot map', 'h1');

        $frameSelector = sprintf('[data-miro-frame="%s"]', self::FRAME_ID);
        $I->see('House Morwen', $frameSelector . ' summary');
        $I->see('Lady Morwen', $frameSelector);
        $I->see('Cousin Elric', $frameSelector);
        $I->dontSee('Council convenes', $frameSelector);

        $I->see('Council convenes', '[data-miro-frame="none"]');
        $I->see('Card', '[data-miro-entry="3458764600000000012"] .badge');
        $I->see('Timeline', '[data-miro-entry="3458764600000000012"] [data-miro-tag]');
        $I->see('Main plot', '[data-miro-entry="3458764600000000010"] [data-miro-tag]');
        $I->see('Not linked', '[data-miro-entry="3458764600000000010"]');

        $I->see('Lady Morwen — suspects → Cousin Elric', '[data-miro-connectors]');
        $I->seeElement(sprintf('a[href="https://miro.com/app/board/uXjVOD6LSME=/?moveToWidget=%s"]', self::LADY_MORWEN_ID));
        $I->seeElement('details.miro-live-embed[data-lazy-embed-src-value="https://miro.com/app/live-embed/uXjVOD6LSME=/?autoplay=true"]');
        $I->dontSeeElement('details.miro-live-embed[open]');
        $I->seeElement(sprintf('button.btn-success[data-bs-target="#miroLinkModal"][data-item-id="%s"]', self::BOARD_ID));
    }

    public function storyWriterLinksAnItemAndADuplicateIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('link a Miro item to a story object as a story writer and see a second identical link rejected');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $board = $this->createBoard($larp, $organizer);
        $character = CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->create(['title' => 'Lady Morwen of Ardenne']);
        $storyWriter = $this->createParticipant($larp, 'storyWriter');

        $I->amLoggedInAs($storyWriter);
        $I->amOnRoute('backoffice_larp_integration_miro_browser_show', $this->boardRoute($larp, $board));
        $I->seeResponseCodeIsSuccessful();

        $I->submitForm(self::LINK_FORM, [
            'target' => self::LADY_MORWEN_ID,
            'storyObject' => $character->getId()->toRfc4122(),
            'role' => ReferenceRole::Primary->value,
        ]);
        $I->seeResponseCodeIsSuccessful();
        $I->see('Linked to "Lady Morwen of Ardenne".');
        $I->see('Lady Morwen of Ardenne', sprintf('[data-miro-entry="%s"] [data-miro-reference]', self::LADY_MORWEN_ID));

        $references = $this->referencesOf($I, $character->_real());
        $I->assertCount(1, $references);
        $reference = $references[0];
        $I->assertSame(LarpIntegrationProvider::Miro, $reference->getProvider());
        $I->assertSame(ReferenceType::BoardItem, $reference->getReferenceType());
        $I->assertSame(ReferenceRole::Primary, $reference->getRole());
        $I->assertSame('Lady Morwen', $reference->getName());
        $I->assertSame('https://miro.com/app/board/uXjVOD6LSME=/?moveToWidget=' . self::LADY_MORWEN_ID, $reference->getUrl());
        $I->assertTrue($storyWriter->getId()->equals($reference->getCreatedBy()?->getId()));
        $linkedItem = $reference->getExternalItem();
        $I->assertInstanceOf(ExternalItem::class, $linkedItem);
        $I->assertTrue($board->getId()->equals($linkedItem->getSource()->getId()));
        $I->assertSame(self::FRAME_ID, $linkedItem->getParentExternalId());
        $I->assertSame(ExternalItemReviewStatus::Linked, $linkedItem->getReviewStatus());

        $I->submitForm(self::LINK_FORM, [
            'target' => self::LADY_MORWEN_ID,
            'storyObject' => $character->getId()->toRfc4122(),
            'role' => ReferenceRole::Mention->value,
        ]);
        $I->see('This is already linked to "Lady Morwen of Ardenne".');
        $I->assertCount(1, $this->referencesOf($I, $character->_real()));
    }

    public function frameAndWholeBoardCanBeLinkedAndUnlinked(FunctionalTester $I): void
    {
        $I->wantTo('link a frame and the whole board to a story object, then unlink the board');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $board = $this->createBoard($larp, $organizer);
        $character = CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->create(['title' => 'Morwen family']);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_miro_browser_show', $this->boardRoute($larp, $board));
        $I->submitForm(self::LINK_FORM, ['target' => self::FRAME_ID, 'storyObject' => $character->getId()->toRfc4122()]);
        $I->submitForm(self::LINK_FORM, ['target' => self::BOARD_ID, 'storyObject' => $character->getId()->toRfc4122()]);

        $referencesByType = [];
        foreach ($this->referencesOf($I, $character->_real()) as $reference) {
            $referencesByType[$reference->getReferenceType()->value] = $reference;
        }
        $referenceTypes = array_keys($referencesByType);
        sort($referenceTypes);
        $I->assertSame([ReferenceType::Board->value, ReferenceType::BoardFrame->value], $referenceTypes);
        $boardReference = $referencesByType[ReferenceType::Board->value];
        $I->assertSame(self::BOARD_ID, $boardReference->getExternalId());
        $I->assertSame('https://miro.com/app/board/uXjVOD6LSME=/', $boardReference->getUrl());
        $I->assertTrue($board->getId()->equals($boardReference->getSource()?->getId()));
        $I->assertNull($boardReference->getExternalItem());
        $I->assertSame(self::FRAME_ID, $referencesByType[ReferenceType::BoardFrame->value]->getExternalId());
        $I->see('Morwen family', '[data-miro-board-links] [data-miro-reference]');
        $I->see('Morwen family', sprintf('[data-miro-entry="%s"][data-miro-type="frame"] [data-miro-reference]', self::FRAME_ID));

        $contentProvider = new MiroSourceContentProvider($I->grabService(MiroBoardSnapshotProvider::class));
        $I->assertTrue($contentProvider->supports($boardReference));
        $frameContent = $contentProvider->fetchContent($referencesByType[ReferenceType::BoardFrame->value]);
        $I->assertSame("House Morwen\n\nLady Morwen\n\nCousin Elric", $frameContent->plainText);
        $I->assertCount(10, $contentProvider->fetchContent($boardReference)->chunks);

        $boardReferenceId = $boardReference->getId()->toRfc4122();
        $I->submitForm(sprintf('[data-miro-board-links] [data-miro-reference="%s"] form[name="miro_board_unlink"]', $boardReferenceId), []);
        $I->see('Unlinked from "Morwen family".');

        $remainingReferences = $this->referencesOf($I, $character->_real());
        $I->assertCount(1, $remainingReferences);
        $I->assertSame(ReferenceType::BoardFrame, $remainingReferences[0]->getReferenceType());
        $I->assertNotNull($I->getEntityManager()->find(Character::class, $character->getId()));
    }

    public function snapshotIsCachedUntilForgotten(FunctionalTester $I): void
    {
        $I->wantTo('see the board snapshot served from its cache pool until it is forgotten');

        $organizer = UserFactory::createApprovedUser();
        $board = $this->createBoard(LarpFactory::createDraftLarp($organizer), $organizer);
        /** @var MiroBoardSnapshotProvider $snapshotProvider */
        $snapshotProvider = $I->grabService(MiroBoardSnapshotProvider::class);
        $router = $this->miroRouter($I);

        $firstSnapshot = $snapshotProvider->snapshot($board->getIntegration(), self::BOARD_ID);
        $requestsAfterFirstBuild = count($router->getRequests());
        $I->assertGreaterThan(0, $requestsAfterFirstBuild);

        $cachedSnapshot = $snapshotProvider->snapshot($board->getIntegration(), self::BOARD_ID);
        $I->assertSame($requestsAfterFirstBuild, count($router->getRequests()));
        $I->assertSame($firstSnapshot->contentHash(), $cachedSnapshot->contentHash());

        $snapshotProvider->forget($board->getIntegration(), self::BOARD_ID);
        $snapshotProvider->snapshot($board->getIntegration(), self::BOARD_ID);
        $I->assertGreaterThan($requestsAfterFirstBuild, count($router->getRequests()));
    }

    public function refreshRequestsASync(FunctionalTester $I): void
    {
        $I->wantTo('refresh a Miro board and see a sync of the board queued');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $board = $this->createBoard($larp, $organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_miro_browser_show', $this->boardRoute($larp, $board));

        $I->stopFollowingRedirects();
        $I->submitForm('form[name="miro_board_refresh"]', []);
        $I->seeResponseCodeIsRedirection();

        $sentEnvelopes = $this->integrationsTransport($I)->getSent();
        $I->assertCount(1, $sentEnvelopes);
        $I->assertInstanceOf(SyncExternalSourceMessage::class, $sentEnvelopes[0]->getMessage());
        $I->assertTrue($board->getId()->equals($sentEnvelopes[0]->getMessage()->sourceId));

        $I->followRedirect();
        $I->see('The board will be reloaded from Miro in a moment.');
    }

    public function storyWriterReachesBoardsFromTheStorySourcesTab(FunctionalTester $I): void
    {
        $I->wantTo('see the Miro boards of the LARP offered on a story object Sources tab');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $board = $this->createBoard($larp, $organizer);
        $character = CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->create();
        $storyWriter = $this->createParticipant($larp, 'storyWriter');

        $I->amLoggedInAs($storyWriter);
        $I->amOnRoute('backoffice_larp_story_sources_list', ['larp' => $larp->getId(), 'storyObject' => $character->getId()]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('[data-miro-board-menu]');
        $I->seeElement(sprintf('a.dropdown-item[href="%s"]', $I->getUrl('backoffice_larp_integration_miro_browser_show', $this->boardRoute($larp, $board))));
    }

    public function playersCannotBrowseOrLink(FunctionalTester $I): void
    {
        $I->wantTo('verify a player can neither browse a Miro board nor link its items');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $board = $this->createBoard($larp, $organizer);
        $character = CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->create();
        $player = $this->createParticipant($larp, 'player');

        $I->amLoggedInAs($player);
        $I->amOnRoute('backoffice_larp_integration_miro_browser_show', $this->boardRoute($larp, $board));
        $I->seeResponseCodeIs(403);

        $I->stopFollowingRedirects();
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integration_miro_browser_link', $this->boardRoute($larp, $board)), [
            'target' => self::LADY_MORWEN_ID,
            'storyObject' => $character->getId()->toRfc4122(),
            '_token' => 'irrelevant',
        ]);
        $I->seeResponseCodeIs(403);
        $I->assertCount(0, $this->referencesOf($I, $character->_real()));
    }

    public function linkingRequiresAValidCsrfTokenAndABoardOfThisLarp(FunctionalTester $I): void
    {
        $I->wantTo('verify linking needs a CSRF token and that boards of another LARP are not reachable');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $board = $this->createBoard($larp, $organizer);
        $character = CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->create();

        $otherOrganizer = UserFactory::createApprovedUser();
        $otherBoard = $this->createBoard(LarpFactory::createDraftLarp($otherOrganizer), $otherOrganizer);

        $I->amLoggedInAs($organizer);
        $I->stopFollowingRedirects();
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integration_miro_browser_link', $this->boardRoute($larp, $board)), [
            'target' => self::LADY_MORWEN_ID,
            'storyObject' => $character->getId()->toRfc4122(),
            '_token' => 'forged',
        ]);
        $I->seeResponseCodeIs(403);
        $I->assertCount(0, $this->referencesOf($I, $character->_real()));

        $I->amOnRoute('backoffice_larp_integration_miro_browser_show', $this->boardRoute($larp, $otherBoard));
        $I->seeResponseCodeIs(404);
    }

    public function revokedMiroAccessShowsTheReconnectProblem(FunctionalTester $I): void
    {
        $I->wantTo('see the board browser explain that Miro needs to be reconnected after a 401');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $board = $this->createBoard($larp, $organizer);

        $router = $this->miroRouter($I);
        $router->on('GET', '/boards/{board_id}/tags', 'unauthorized.json', 401);
        try {
            $I->amLoggedInAs($organizer);
            $I->amOnRoute('backoffice_larp_integration_miro_browser_show', $this->boardRoute($larp, $board));
        } finally {
            $router->on('GET', '/boards/{board_id}/tags', 'tags.json');
        }

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('[data-miro-problem="needs_reauth"]');
        $I->seeElement(sprintf('a.btn-warning[href="%s"]', $I->getUrl('backoffice_larp_connect_integration', [
            'larp' => $larp->getId()->toRfc4122(),
            'provider' => LarpIntegrationProvider::Miro->value,
        ])));
    }

    private function createBoard(mixed $larp, mixed $organizer): ExternalSource
    {
        $integration = LarpIntegrationFactory::new()
            ->forLarp($larp)
            ->createdBy($organizer)
            ->miro()
            ->with([
                'accessToken' => 'valid-miro-access-token',
                'externalAccountId' => FakeMiroOAuthGateway::TEAM_ID,
                'scopes' => MiroOAuthProvider::BOARDS_READ_SCOPE,
            ]);

        return ExternalSourceFactory::new([
            'integration' => $integration,
            'kind' => ExternalSourceKind::MiroBoard,
            'externalId' => self::BOARD_ID,
            'title' => 'Shadows of Ardenne — plot map',
            'url' => 'https://miro.com/app/board/uXjVOD6LSME=',
        ])->create()->_real();
    }

    private function createParticipant(mixed $larp, string $role): User
    {
        $user = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($user)->{$role}()->create();

        return $user;
    }

    /**
     * @return array{larp: string, source: string}
     */
    private function boardRoute(mixed $larp, ExternalSource $board): array
    {
        return ['larp' => $larp->getId()->toRfc4122(), 'source' => $board->getId()->toRfc4122()];
    }

    private function syncHandler(FunctionalTester $I): SyncExternalSourceHandler
    {
        return $I->grabService(SyncExternalSourceHandler::class);
    }

    private function linker(FunctionalTester $I): ExternalReferenceLinker
    {
        return $I->grabService(ExternalReferenceLinker::class);
    }

    private function miroRouter(FunctionalTester $I): FixtureRouter
    {
        return $I->grabService('tests.integrations.miro.fixture_router');
    }

    private function integrationsTransport(FunctionalTester $I): InMemoryTransport
    {
        return $I->grabService('messenger.transport.integrations');
    }

    private function reloadBoard(FunctionalTester $I, ExternalSource $board): ExternalSource
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();

        $reloadedBoard = $entityManager->find(ExternalSource::class, $board->getId());
        $I->assertInstanceOf(ExternalSource::class, $reloadedBoard);

        return $reloadedBoard;
    }

    /**
     * @return array<string, ExternalItem>
     */
    private function itemsByExternalId(FunctionalTester $I, ExternalSource $board): array
    {
        $itemsByExternalId = [];
        foreach ($I->getEntityManager()->getRepository(ExternalItem::class)->findBy(['source' => $board->getId()]) as $item) {
            $itemsByExternalId[$item->getExternalId()] = $item;
        }

        return $itemsByExternalId;
    }

    /**
     * @return list<ExternalReference>
     */
    private function referencesOf(FunctionalTester $I, Character $character): array
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();

        return array_values($entityManager->getRepository(ExternalReference::class)->findBy(['storyObject' => $character->getId()], ['createdAt' => 'ASC']));
    }
}
