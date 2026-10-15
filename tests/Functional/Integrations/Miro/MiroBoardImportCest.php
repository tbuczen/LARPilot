<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Miro;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Miro\OAuth\MiroOAuthProvider;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Enum\RelationType;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Entity\Place;
use App\Domain\StoryObject\Entity\Relation;
use App\Domain\StoryObject\Entity\StoryObject;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\Integrations\ExternalSourceFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\Factory\StoryObject\FactionFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Http\FixtureRouter;
use Tests\Support\Http\IntegrationFixtureRouters;
use Tests\Support\Integrations\FakeMiroOAuthGateway;

class MiroBoardImportCest
{
    private const BOARD_ID = IntegrationFixtureRouters::MIRO_BOARD_ID;
    private const FRAME_ID = IntegrationFixtureRouters::MIRO_FRAME_ID;
    private const CAPTAIN_ID = '3458764600000000201';
    private const LIGHTHOUSE_ID = '3458764600000000202';
    private const BROTHER_TOMAS_ID = '3458764600000000203';
    private const GUILD_ID = '3458764600000000011';
    private const LADY_MORWEN_ID = '3458764600000000101';
    private const COUSIN_ELRIC_ID = '3458764600000000102';
    private const COUSIN_CONNECTOR_ID = '3458764600000000501';
    private const SUSPECTS_CONNECTOR_ID = '3458764600000000502';
    private const FRIEND_OF_PLACE_CONNECTOR_ID = '3458764600000000503';
    private const GUILD_ALLY_CONNECTOR_ID = '3458764600000000504';
    private const FRIENDSHIP_CONNECTOR_ID = '3458764600000000505';
    private const IMPORT_FORM = 'form[name="miro_board_import"]';

    public function _before(FunctionalTester $I): void
    {
        $this->miroRouter($I)
            ->on('GET', '/boards/{board_id}/items', 'import_board_items.json')
            ->on('GET', '/boards/{board_id}/items?parent_item_id=' . self::FRAME_ID, 'import_frame_children.json')
            ->on('GET', '/boards/{board_id}/connectors', 'import_connectors.json')
            ->on('GET', '/boards/{board_id}/tags', 'import_tags.json');
    }

    public function _after(FunctionalTester $I): void
    {
        $this->miroRouter($I)
            ->on('GET', '/boards/{board_id}/items', 'board_items_page_1.json')
            ->on('GET', '/boards/{board_id}/items?parent_item_id=' . self::FRAME_ID, 'frame_children.json')
            ->on('GET', '/boards/{board_id}/connectors', 'connectors.json')
            ->on('GET', '/boards/{board_id}/tags', 'tags.json');
    }

    public function previewSuggestsLinksCreatesAndRelationsWithoutWriting(FunctionalTester $I): void
    {
        $I->wantTo('preview a Miro import with suggestions for every rule and nothing written');

        [$organizer, $larp, $board] = $this->setUpBoard();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_miro_browser_show', $this->boardRoute($larp, $board));
        $previewUrl = $I->getUrl('backoffice_larp_integration_miro_import_preview', $this->boardRoute($larp, $board));
        $I->seeElement(sprintf('a.btn-outline-primary[data-miro-import-board][href="%s"]', $previewUrl));

        $I->amOnPage($previewUrl);
        $I->seeResponseCodeIsSuccessful();

        $I->seeElement($this->row(self::FRAME_ID, 'link'));
        $I->see('House Morwen', $this->row(self::FRAME_ID, 'link'));
        $I->seeElement($this->row(self::CAPTAIN_ID, 'link'));
        $I->see('Captain Mira Valé', $this->row(self::CAPTAIN_ID, 'link'));
        $I->dontSeeElement($this->row(self::CAPTAIN_ID, 'link') . ' [data-miro-import-confidence="low"]');
        $I->seeElement($this->row(self::LIGHTHOUSE_ID, 'create'));
        $I->seeElement($this->row(self::BROTHER_TOMAS_ID, 'link') . ' [data-miro-import-confidence="low"]');
        $I->seeElement($this->row(self::LADY_MORWEN_ID, 'create'));
        $I->seeElement($this->row(self::COUSIN_ELRIC_ID, 'create'));
        $I->seeElement($this->row(self::COUSIN_CONNECTOR_ID, 'create_relation'));
        $I->seeElement($this->row(self::FRIENDSHIP_CONNECTOR_ID, 'create_relation'));
        $I->seeNumberOfElements(self::IMPORT_FORM . ' input[name="rows[]"]', 8);
        $I->seeNumberOfElements(self::IMPORT_FORM . ' input[name="rows[]"]:checked', 0);

        $I->seeElement($this->skipped(self::GUILD_ID, 'unmapped'));
        $I->seeElement($this->skipped(self::SUSPECTS_CONNECTOR_ID, 'needs_type'));
        $I->seeElement($this->skipped(self::FRIEND_OF_PLACE_CONNECTOR_ID, 'incompatible'));
        $I->seeElement($this->skipped(self::GUILD_ALLY_CONNECTOR_ID, 'unmapped'));
        $I->seeElement(sprintf('[data-miro-import-membership="%s"]', self::LADY_MORWEN_ID));
        $I->seeElement(sprintf('[data-miro-import-membership="%s"]', self::COUSIN_ELRIC_ID));

        $I->assertSame(0, $this->countOf($I, ExternalReference::class, $larp));
        $I->assertSame(0, $this->countOf($I, Relation::class, $larp));
        $I->assertSame(2, count($I->getEntityManager()->getRepository(Character::class)->findBy(['larp' => $larp->getId()])));
    }

    public function importCreatesAndLinksOnlyConfirmedRows(FunctionalTester $I): void
    {
        $I->wantTo('import only the confirmed rows of a Miro board in one go and see a re-run show only what is left');

        [$organizer, $larp, $board] = $this->setUpBoard();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_miro_import_preview', $this->boardRoute($larp, $board));
        foreach ([self::FRAME_ID, self::CAPTAIN_ID, self::LADY_MORWEN_ID, self::COUSIN_ELRIC_ID, self::COUSIN_CONNECTOR_ID] as $confirmedId) {
            $I->checkOption(sprintf('tr[data-miro-import-row="%s"] input[name="rows[]"]', $confirmedId));
        }
        $I->click(self::IMPORT_FORM . ' button[type="submit"]');
        $I->seeResponseCodeIsSuccessful();
        $I->see('2 story objects created, 4 Miro elements linked, 1 relations created');

        $entityManager = $I->getEntityManager();
        $entityManager->clear();
        $ladyMorwen = $this->findByTitle($I, Character::class, $larp, 'Lady Morwen');
        $cousinElric = $this->findByTitle($I, Character::class, $larp, 'Cousin Elric');
        $I->assertSame($organizer->getId()->toRfc4122(), $ladyMorwen->getCreatedBy()?->getId()->toRfc4122());
        $I->assertSame([self::LADY_MORWEN_ID], $this->linkedExternalIds($I, $ladyMorwen));
        $I->assertSame([self::COUSIN_ELRIC_ID], $this->linkedExternalIds($I, $cousinElric));
        $I->assertSame([self::CAPTAIN_ID], $this->linkedExternalIds($I, $this->findByTitle($I, Character::class, $larp, 'Captain Mira Valé')));
        $I->assertSame([self::FRAME_ID], $this->linkedExternalIds($I, $this->findByTitle($I, Faction::class, $larp, 'House Morwen')));
        $I->assertSame([], $this->linkedExternalIds($I, $this->findByTitle($I, Character::class, $larp, 'Brother Tomas')));
        $I->assertSame([], $entityManager->getRepository(Place::class)->findBy(['larp' => $larp->getId()]));

        $relations = $entityManager->getRepository(Relation::class)->findBy(['larp' => $larp->getId()]);
        $I->assertCount(1, $relations);
        $I->assertSame(RelationType::Family, $relations[0]->getRelationType());
        $I->assertTrue($ladyMorwen->getId()->equals($relations[0]->getFrom()?->getId()));
        $I->assertTrue($cousinElric->getId()->equals($relations[0]->getTo()?->getId()));
        $I->assertSame([self::COUSIN_CONNECTOR_ID], $this->linkedExternalIds($I, $relations[0]));

        $I->amOnRoute('backoffice_larp_integration_miro_import_preview', $this->boardRoute($larp, $board));
        $I->seeNumberOfElements(self::IMPORT_FORM . ' input[name="rows[]"]', 3);
        $I->seeElement($this->row(self::LIGHTHOUSE_ID, 'create'));
        $I->seeElement($this->row(self::BROTHER_TOMAS_ID, 'link'));
        $I->seeElement($this->row(self::FRIENDSHIP_CONNECTOR_ID, 'create_relation'));
        $I->seeElement('[data-miro-import-unchanged="5"]');
        $I->seeElement($this->skipped(self::FRIEND_OF_PLACE_CONNECTOR_ID, 'incompatible'));
    }

    public function rerunAfterFullImportShowsNoChanges(FunctionalTester $I): void
    {
        $I->wantTo('see no changes when re-running an import after everything was imported');

        [$organizer, $larp, $board] = $this->setUpBoard();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_miro_import_preview', $this->boardRoute($larp, $board));
        foreach ($I->grabMultiple(self::IMPORT_FORM . ' tbody tr', 'data-miro-import-row') as $rowId) {
            $I->checkOption(sprintf('tr[data-miro-import-row="%s"] input[name="rows[]"]', $rowId));
        }
        $I->click(self::IMPORT_FORM . ' button[type="submit"]');
        $I->seeResponseCodeIsSuccessful();
        $I->see('3 story objects created, 6 Miro elements linked, 2 relations created');

        $referenceCount = $this->countOf($I, ExternalReference::class, $larp);
        $relationCount = $this->countOf($I, Relation::class, $larp);
        $I->assertSame(8, $referenceCount);
        $I->assertSame(2, $relationCount);

        $I->amOnRoute('backoffice_larp_integration_miro_import_preview', $this->boardRoute($larp, $board));
        $I->seeElement('[data-miro-import-empty]');
        $I->dontSeeElement(self::IMPORT_FORM);
        $I->seeElement('[data-miro-import-unchanged="8"]');
        $I->seeElement($this->skipped(self::FRIEND_OF_PLACE_CONNECTOR_ID, 'incompatible'));
        $I->seeElement($this->skipped(self::SUSPECTS_CONNECTOR_ID, 'needs_type'));
        $I->assertSame(1, count($I->getEntityManager()->getRepository(Place::class)->findBy(['larp' => $larp->getId()])));
        $I->assertSame($referenceCount, $this->countOf($I, ExternalReference::class, $larp));
        $I->assertSame($relationCount, $this->countOf($I, Relation::class, $larp));
    }

    public function nothingIsWrittenWithoutConfirmingRows(FunctionalTester $I): void
    {
        $I->wantTo('see that submitting the import without ticking rows writes nothing');

        [$organizer, $larp, $board] = $this->setUpBoard();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_miro_import_preview', $this->boardRoute($larp, $board));
        $I->click(self::IMPORT_FORM . ' button[type="submit"]');
        $I->seeResponseCodeIsSuccessful();
        $I->see('Nothing was imported: tick the rows you want to import first.');

        $I->assertSame(0, $this->countOf($I, ExternalReference::class, $larp));
        $I->assertSame(0, $this->countOf($I, Relation::class, $larp));
        $I->assertSame(2, count($I->getEntityManager()->getRepository(Character::class)->findBy(['larp' => $larp->getId()])));
    }

    public function relationIsSkippedWhenItsNewObjectsAreNotConfirmed(FunctionalTester $I): void
    {
        $I->wantTo('see a confirmed relation skipped when the story objects it connects are not imported');

        [$organizer, $larp, $board] = $this->setUpBoard();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_miro_import_preview', $this->boardRoute($larp, $board));
        $I->checkOption(sprintf('tr[data-miro-import-row="%s"] input[name="rows[]"]', self::COUSIN_CONNECTOR_ID));
        $I->click(self::IMPORT_FORM . ' button[type="submit"]');
        $I->seeResponseCodeIsSuccessful();
        $I->see('1 relation was skipped because one of its story objects was not imported.');

        $I->assertSame(0, $this->countOf($I, ExternalReference::class, $larp));
        $I->assertSame(0, $this->countOf($I, Relation::class, $larp));
    }

    public function forgedRowTokensAndInvalidCsrfWriteNothing(FunctionalTester $I): void
    {
        $I->wantTo('see the import reject forged rows and requests without a valid CSRF token');

        [$organizer, $larp, $board] = $this->setUpBoard();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_miro_import_preview', $this->boardRoute($larp, $board));
        $csrfToken = $I->grabValueFrom(self::IMPORT_FORM . ' input[name="_token"]');

        $applyUrl = $I->getUrl('backoffice_larp_integration_miro_import_apply', $this->boardRoute($larp, $board));
        $I->sendAjaxPostRequest($applyUrl, ['_token' => $csrfToken, 'rows' => ['forged-token']]);
        $I->seeResponseCodeIsSuccessful();
        $I->see('Nothing was imported: the board changed since the preview.');
        $I->sendAjaxPostRequest($applyUrl, ['_token' => 'invalid', 'rows' => ['forged-token']]);
        $I->seeResponseCodeIs(403);

        $I->assertSame(0, $this->countOf($I, ExternalReference::class, $larp));
        $I->assertSame(0, $this->countOf($I, Relation::class, $larp));
    }

    public function storyWriterCanImportAndPlayerCannot(FunctionalTester $I): void
    {
        $I->wantTo('see the Miro import open to organizers and story writers only');

        [, $larp, $board] = $this->setUpBoard();

        $storyWriter = $this->createParticipant($larp, 'storyWriter');
        $I->amLoggedInAs($storyWriter);
        $I->amOnRoute('backoffice_larp_integration_miro_import_preview', $this->boardRoute($larp, $board));
        $I->seeResponseCodeIsSuccessful();
        $I->seeElement(self::IMPORT_FORM);

        $player = $this->createParticipant($larp, 'player');
        $I->amLoggedInAs($player);
        $I->amOnRoute('backoffice_larp_integration_miro_import_preview', $this->boardRoute($larp, $board));
        $I->seeResponseCodeIs(403);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integration_miro_import_apply', $this->boardRoute($larp, $board)), ['rows' => ['any']]);
        $I->seeResponseCodeIs(403);
    }

    /**
     * @return array{User, Larp, ExternalSource}
     */
    private function setUpBoard(): array
    {
        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->withTitle('Captain Mira Valé')->create();
        CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->withTitle('Brother Tomas')->create();
        FactionFactory::new()->forLarp($larp)->withTitle('House Morwen')->create();

        $integration = LarpIntegrationFactory::new()
            ->forLarp($larp)
            ->createdBy($organizer)
            ->miro()
            ->with([
                'accessToken' => 'valid-miro-access-token',
                'externalAccountId' => FakeMiroOAuthGateway::TEAM_ID,
                'scopes' => MiroOAuthProvider::BOARDS_READ_SCOPE,
            ]);

        $board = ExternalSourceFactory::new([
            'integration' => $integration,
            'kind' => ExternalSourceKind::MiroBoard,
            'externalId' => self::BOARD_ID,
            'title' => 'Shadows of Ardenne — plot map',
            'url' => 'https://miro.com/app/board/uXjVOD6LSME=',
        ])->create()->_real();

        return [$organizer, $larp->_real(), $board];
    }

    private function createParticipant(Larp $larp, string $role): User
    {
        $user = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($user)->{$role}()->create();

        return $user;
    }

    /**
     * @return array{larp: string, source: string}
     */
    private function boardRoute(Larp $larp, ExternalSource $board): array
    {
        return ['larp' => $larp->getId()->toRfc4122(), 'source' => $board->getId()->toRfc4122()];
    }

    private function row(string $externalId, string $action): string
    {
        return sprintf('%s tr[data-miro-import-row="%s"][data-miro-import-action="%s"]', self::IMPORT_FORM, $externalId, $action);
    }

    private function skipped(string $externalId, string $action): string
    {
        return sprintf('[data-miro-import-not-importable] [data-miro-import-skipped="%s"][data-miro-import-action="%s"]', $externalId, $action);
    }

    /**
     * @template T of StoryObject
     * @param class-string<T> $storyObjectClass
     * @return T
     */
    private function findByTitle(FunctionalTester $I, string $storyObjectClass, Larp $larp, string $title): StoryObject
    {
        $storyObject = $I->getEntityManager()->getRepository($storyObjectClass)->findOneBy(['larp' => $larp->getId(), 'title' => $title]);
        $I->assertInstanceOf($storyObjectClass, $storyObject);

        return $storyObject;
    }

    /**
     * @return list<string>
     */
    private function linkedExternalIds(FunctionalTester $I, StoryObject $storyObject): array
    {
        $references = $I->getEntityManager()->getRepository(ExternalReference::class)->findBy(['storyObject' => $storyObject->getId()]);

        return array_map(static fn (ExternalReference $reference): string => $reference->getExternalId(), $references);
    }

    /**
     * @param class-string $entityClass
     */
    private function countOf(FunctionalTester $I, string $entityClass, Larp $larp): int
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();

        return $entityManager->getRepository($entityClass)->count(['larp' => $larp->getId()]);
    }

    private function miroRouter(FunctionalTester $I): FixtureRouter
    {
        return $I->grabService('tests.integrations.miro.fixture_router');
    }
}
