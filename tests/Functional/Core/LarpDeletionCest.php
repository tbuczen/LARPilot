<?php

declare(strict_types=1);

namespace Tests\Functional\Core;

use App\Domain\Account\Entity\User;
use App\Domain\Application\Entity\LarpApplicationChoice;
use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\CallRecordingTrack;
use App\Domain\CallTranscript\Service\CallRecordingStorage;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpInvitation;
use App\Domain\Core\Entity\SavedFormFilter;
use App\Domain\Core\Entity\Tag;
use App\Domain\Gallery\Entity\Gallery;
use App\Domain\Incidents\Entity\LarpIncident;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Kanban\Entity\KanbanTask;
use App\Domain\Map\Entity\MapLocation;
use App\Domain\Map\Entity\StaffPosition;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\CharacterItem;
use App\Domain\StoryObject\Entity\CharacterSkill;
use App\Domain\StoryObject\Entity\Enum\RelationType;
use App\Domain\StoryObject\Entity\Event;
use App\Domain\StoryObject\Entity\Item;
use App\Domain\StoryObject\Entity\Place;
use App\Domain\StoryObject\Entity\Quest;
use App\Domain\StoryObject\Entity\Relation;
use App\Domain\StoryObject\Entity\Skill;
use App\Domain\StoryObject\Entity\StoryObject;
use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Money\Money;
use Symfony\Component\Uid\Uuid;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Application\LarpApplicationChoiceFactory;
use Tests\Support\Factory\Application\LarpApplicationFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\Integrations\ExternalItemFactory;
use Tests\Support\Factory\Integrations\ExternalReferenceFactory;
use Tests\Support\Factory\Integrations\ExternalSourceFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\Integrations\SharedFileFactory;
use Tests\Support\Factory\Mailing\MailTemplateFactory;
use Tests\Support\Factory\Map\GameMapFactory;
use Tests\Support\Factory\Map\StaffPositionFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\Factory\StoryObject\FactionFactory;
use Tests\Support\Factory\StoryObject\KnowledgeDocumentFactory;
use Tests\Support\Factory\StoryObject\ThreadFactory;
use Tests\Support\Factory\Survey\SurveyFactory;
use Tests\Support\Factory\Survey\SurveyResponseFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Http\FixtureRouter;
use Tests\Support\Http\IntegrationFixtureRouters;

class LarpDeletionCest
{
    private const LARP_TITLE = 'Shadows of Ardenne';
    private const GUILD_ID = IntegrationFixtureRouters::DISCORD_GUILD_ID;

    private const TABLES_OWNED_BY_LARP = [
        'call_recording',
        'external_reference',
        'gallery',
        'game_map',
        'kanban_task',
        'knowledge_document',
        'larp_application',
        'larp_incident',
        'larp_integration',
        'larp_invitation',
        'larp_participant',
        'mail_template',
        'object_field_mapping',
        'planning_resource',
        'saved_form_filter',
        'scheduled_event',
        'skill',
        'story_object',
        'survey',
        'survey_response',
        'tag',
    ];

    private const TABLES_WITHOUT_FIXTURES = [
        'object_field_mapping',
        'planning_resource',
        'scheduled_event',
    ];

    public function _before(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
    }

    public function organizerDeletesDraftLarpWithAllItsData(FunctionalTester $I): void
    {
        $I->wantTo('delete a draft LARP and everything it owns as its organizer');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer, self::LARP_TITLE);
        $larpId = $larp->getId();
        $otherLarp = LarpFactory::createPublishedLarp(UserFactory::createApprovedUser());
        $continuedCharacter = $this->createRichLarpData($I, $larp->_real(), $organizer, $otherLarp->_real());
        $recordingDirectory = $this->createCallRecordingWithAudio($I, $larp->_real(), $organizer);
        $discordRouter = $this->persistedDiscordRouter($I);
        $requestsBefore = count($discordRouter->getRequests());
        foreach (array_diff(self::TABLES_OWNED_BY_LARP, self::TABLES_WITHOUT_FIXTURES) as $table) {
            $I->assertGreaterThan(0, $this->countRowsOfLarp($I, $table, $larpId), sprintf('Fixture rows of %s should exist', $table));
        }

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_status_index', ['larp' => $larpId]);
        $I->seeResponseCodeIsSuccessful();
        $I->submitForm('form[name="larp_delete"]', ['confirmTitle' => self::LARP_TITLE]);

        $I->seeResponseCodeIsRedirection();
        $I->followRedirect();
        $I->seeCurrentRouteIs('backoffice_larp_list');
        $I->see(sprintf('LARP "%s" has been deleted.', self::LARP_TITLE));

        $entityManager = $I->getEntityManager();
        $entityManager->clear();
        $I->assertNull($entityManager->getRepository(Larp::class)->find($larpId));
        $I->assertSame(0, (int) $entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM larp WHERE id = :id', ['id' => $larpId->toRfc4122()]));
        foreach (self::TABLES_OWNED_BY_LARP as $table) {
            $I->assertSame(0, $this->countRowsOfLarp($I, $table, $larpId), sprintf('Rows of %s should be deleted with the LARP', $table));
        }

        $I->assertSame(0, $entityManager->getRepository(ExternalSource::class)->count([]));
        $I->assertSame(0, $entityManager->getRepository(ExternalItem::class)->count([]));
        $I->assertSame(0, $entityManager->getRepository(SharedFile::class)->count([]));
        $I->assertSame(0, $entityManager->getRepository(StaffPosition::class)->count([]));
        $I->assertSame(0, $entityManager->getRepository(MapLocation::class)->count([]));
        $I->assertSame(0, $entityManager->getRepository(LarpApplicationChoice::class)->count([]));
        $I->assertSame(0, $entityManager->getRepository(CharacterSkill::class)->count([]));
        $I->assertSame(0, $entityManager->getRepository(CallRecordingTrack::class)->count([]));
        $I->assertDirectoryDoesNotExist($recordingDirectory);

        $I->assertInstanceOf(Larp::class, $entityManager->getRepository(Larp::class)->find($otherLarp->getId()));
        $survivingCharacter = $entityManager->getRepository(Character::class)->find($continuedCharacter);
        $I->assertInstanceOf(Character::class, $survivingCharacter);
        $I->assertNull($survivingCharacter->getPreviousCharacter());

        $I->assertContains('DELETE /users/@me/guilds/' . self::GUILD_ID, $this->requestSummaries($discordRouter, $requestsBefore));
    }

    public function deletingLarpKeepsDiscordBotOnGuildUsedByAnotherLarp(FunctionalTester $I): void
    {
        $I->wantTo('keep the Discord bot on a server another LARP still uses when deleting a draft LARP');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer, self::LARP_TITLE);
        $otherLarp = LarpFactory::createDraftLarp(UserFactory::createApprovedUser());
        $this->createDiscordIntegration($larp, $organizer);
        $this->createDiscordIntegration($otherLarp, $organizer);
        $discordRouter = $this->persistedDiscordRouter($I);
        $requestsBefore = count($discordRouter->getRequests());

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
        $I->submitForm('form[name="larp_delete"]', ['confirmTitle' => self::LARP_TITLE]);

        $I->seeResponseCodeIsRedirection();
        $entityManager = $I->getEntityManager();
        $entityManager->clear();
        $I->assertNull($entityManager->getRepository(Larp::class)->find($larp->getId()));
        $I->assertSame(1, $entityManager->getRepository(LarpIntegration::class)->count(['larp' => $otherLarp->getId()]));
        $I->assertNotContains('DELETE /users/@me/guilds/' . self::GUILD_ID, $this->requestSummaries($discordRouter, $requestsBefore));
    }

    public function larpListOffersDeleteOnlyForDrafts(FunctionalTester $I): void
    {
        $I->wantTo('see the delete action on the LARP list only for draft LARPs');

        $organizer = UserFactory::createApprovedUser();
        $draftLarp = LarpFactory::createDraftLarp($organizer, self::LARP_TITLE);
        $publishedLarp = LarpFactory::createPublishedLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_list');

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement(sprintf('#deleteLarpModal-%s form[name="larp_delete"]', $draftLarp->getId()));
        $I->dontSeeElement(sprintf('#deleteLarpModal-%s', $publishedLarp->getId()));
    }

    public function deletionRequiresTypingTheLarpTitle(FunctionalTester $I): void
    {
        $I->wantTo('keep the LARP when the typed title does not match');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer, self::LARP_TITLE);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
        $I->submitForm('form[name="larp_delete"]', ['confirmTitle' => 'Shadows']);

        $I->seeResponseCodeIsRedirection();
        $this->assertLarpExists($I, $larp->getId());
    }

    public function deletionRequiresValidCsrfToken(FunctionalTester $I): void
    {
        $I->wantTo('keep the LARP when the delete request has no valid CSRF token');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer, self::LARP_TITLE);

        $I->amLoggedInAs($organizer);
        $I->sendAjaxPostRequest($this->deleteUrl($I, $larp->getId()), ['_token' => 'invalid', 'confirmTitle' => self::LARP_TITLE]);

        $I->seeResponseCodeIsRedirection();
        $this->assertLarpExists($I, $larp->getId());
    }

    /**
     * @return list<array{status: LarpStageStatus}>
     */
    protected function nonDraftStatuses(): array
    {
        return [
            ['status' => LarpStageStatus::WIP],
            ['status' => LarpStageStatus::PUBLISHED],
            ['status' => LarpStageStatus::INQUIRIES],
            ['status' => LarpStageStatus::CONFIRMED],
            ['status' => LarpStageStatus::CANCELLED],
            ['status' => LarpStageStatus::COMPLETED],
        ];
    }

    #[DataProvider('nonDraftStatuses')]
    public function nonDraftLarpCannotBeDeleted(FunctionalTester $I, Example $example): void
    {
        $I->wantTo('get 403 when deleting a LARP that is no longer a draft');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::new()->withStatus($example['status'])->withCreator($organizer)->withTitle(self::LARP_TITLE)->create();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($organizer)->organizer()->create();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
        $I->dontSeeElement('form[name="larp_delete"]');
        $I->sendAjaxPostRequest($this->deleteUrl($I, $larp->getId()), ['_token' => 'irrelevant', 'confirmTitle' => self::LARP_TITLE]);

        $I->seeResponseCodeIs(403);
        $this->assertLarpExists($I, $larp->getId());
    }

    public function playerCannotDeleteDraftLarp(FunctionalTester $I): void
    {
        $I->wantTo('verify a player gets 403 when deleting a draft LARP');

        $larp = LarpFactory::createDraftLarp(UserFactory::createApprovedUser(), self::LARP_TITLE);
        $player = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($player)->player()->create();

        $this->assertDeletionForbidden($I, $player, $larp->getId());
    }

    public function storyWriterCannotDeleteDraftLarp(FunctionalTester $I): void
    {
        $I->wantTo('verify a story writer gets 403 when deleting a draft LARP');

        $larp = LarpFactory::createDraftLarp(UserFactory::createApprovedUser(), self::LARP_TITLE);
        $storyWriter = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($storyWriter)->storyWriter()->create();

        $this->assertDeletionForbidden($I, $storyWriter, $larp->getId());
    }

    public function deletionServiceCoversEveryTableReferencingLarp(FunctionalTester $I): void
    {
        $I->wantTo('make sure every table with a foreign key to larp is handled by the LARP deletion service');

        $referencingTables = $I->getEntityManager()->getConnection()->fetchFirstColumn(
            <<<'SQL'
                SELECT DISTINCT tc.table_name
                FROM information_schema.table_constraints tc
                JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name
                WHERE tc.constraint_type = 'FOREIGN KEY' AND ccu.table_name = 'larp'
                ORDER BY tc.table_name
                SQL
        );

        $I->assertSame(self::TABLES_OWNED_BY_LARP, $referencingTables);
    }

    private function createRichLarpData(FunctionalTester $I, Larp $larp, User $organizer, Larp $otherLarp): Uuid
    {
        $entityManager = $I->getEntityManager();

        $player = UserFactory::createApprovedUser();
        $playerParticipant = LarpParticipantFactory::new()->forLarp($larp)->forUser($player)->player()->create()->_real();
        $writerParticipant = LarpParticipantFactory::new()->forLarp($larp)->forUser(UserFactory::createApprovedUser())->storyWriter()->create()->_real();

        $tag = new Tag();
        $tag->setLarp($larp);
        $tag->setCreatedBy($organizer);
        $tag->setTitle('Intrigue');
        $entityManager->persist($tag);

        $character = CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->create()->_real();
        $character->setLarpParticipant($playerParticipant);
        $character->setStoryWriter($writerParticipant);
        $character->addTag($tag);
        $secondCharacter = CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->create()->_real();
        $continuedCharacter = CharacterFactory::new()->forLarp($otherLarp)->withCreator($organizer)->create()->_real();
        $continuedCharacter->setPreviousCharacter($character);
        $character->setContinuation($continuedCharacter);

        $faction = FactionFactory::new()->forLarp($larp)->create()->_real();
        $character->addFaction($faction);
        $thread = ThreadFactory::new()->forLarp($larp)->with(['createdBy' => $organizer])->withInvolvedCharacters(2)->withInvolvedFactions(1)->create()->_real();

        $quest = new Quest();
        $this->prepareStoryObject($quest, $larp, $organizer, 'Recover the relic');
        $quest->setThread($thread);
        $entityManager->persist($quest);

        $place = new Place();
        $this->prepareStoryObject($place, $larp, $organizer, 'Old chapel');
        $entityManager->persist($place);

        $event = new Event();
        $this->prepareStoryObject($event, $larp, $organizer, 'Midnight ritual');
        $event->setThread($thread);
        $event->setPlace($place);
        $entityManager->persist($event);

        $item = new Item();
        $this->prepareStoryObject($item, $larp, $organizer, 'Silver dagger');
        $item->setDesignation($character);
        $item->setCost(Money::PLN(1500));
        $entityManager->persist($item);
        $characterItem = new CharacterItem();
        $characterItem->setCharacter($character);
        $characterItem->setItem($item);
        $entityManager->persist($characterItem);

        $relation = new Relation();
        $this->prepareStoryObject($relation, $larp, $organizer, 'Rivals');
        $relation->setFrom($character);
        $relation->setTo($secondCharacter);
        $relation->setRelationType(RelationType::Enemy);
        $entityManager->persist($relation);

        $skill = new Skill();
        $skill->setLarp($larp);
        $skill->setName('Alchemy');
        $skill->setCreatedBy($organizer);
        $entityManager->persist($skill);
        $characterSkill = new CharacterSkill();
        $characterSkill->setCharacter($character);
        $characterSkill->setSkill($skill);
        $characterSkill->setDescription('Brews potions');
        $entityManager->persist($characterSkill);

        KnowledgeDocumentFactory::new()->forLarp($larp)->withCreator($organizer)->ownedBy($character)->visibleTo($faction)->create();

        $application = LarpApplicationFactory::new()->forLarp($larp)->forUser($player)->create()->_real();
        LarpApplicationChoiceFactory::new()->forApplication($application)->forCharacter($character)->create();
        $playerParticipant->setLarpApplication($application);

        $invitation = new LarpInvitation();
        $invitation->setLarp($larp);
        $invitation->setValidTo(new \DateTimeImmutable('+7 days'));
        $invitation->setInvitedRole(ParticipantRole::PLAYER);
        $invitation->setLarpCharacter($character);
        $entityManager->persist($invitation);

        $survey = SurveyFactory::new()->forLarp($larp)->withQuestions(2)->create()->_real();
        SurveyResponseFactory::new()->forSurvey($survey)->forLarp($larp)->forUser($player)->with(['assignedCharacter' => $character])->withAnswers()->create();

        $gameMap = GameMapFactory::new()->forLarp($larp)->create()->_real();
        StaffPositionFactory::new()->forMap($gameMap)->forParticipant($playerParticipant)->create();
        $mapLocation = new MapLocation();
        $mapLocation->setMap($gameMap);
        $mapLocation->setPlace($place);
        $mapLocation->setName('Chapel');
        $mapLocation->setPositionX(10.0);
        $mapLocation->setPositionY(20.0);
        $mapLocation->setCreatedBy($organizer);
        $entityManager->persist($mapLocation);

        $kanbanTask = new KanbanTask();
        $kanbanTask->setLarp($larp);
        $kanbanTask->setTitle('Print character sheets');
        $kanbanTask->setAssignedTo($writerParticipant);
        $entityManager->persist($kanbanTask);

        $savedFilter = new SavedFormFilter();
        $savedFilter->setLarp($larp);
        $savedFilter->setName('Players');
        $savedFilter->setFormName('character_filter');
        $savedFilter->setParameters(['type' => 'player']);
        $savedFilter->setCreatedBy($organizer);
        $entityManager->persist($savedFilter);

        $gallery = new Gallery();
        $gallery->setLarp($larp);
        $gallery->setTitle('Day one');
        $gallery->setPhotographer($writerParticipant);
        $entityManager->persist($gallery);

        MailTemplateFactory::new()->with(['larp' => $larp])->create();
        $incident = new LarpIncident();
        $incident->setLarp($larp);
        $incident->setReportCode('ABCDEF');
        $incident->setCaseId('INC-0001-ABC');
        $incident->setCreatedAt(new \DateTimeImmutable());
        $incident->setDescription('Lost item reported');
        $incident->setAllowFeedback(false);
        $incident->setContactAccused(false);
        $incident->setAllowMediator(false);
        $incident->setStayAnonymous(true);
        $incident->setCreatedBy($organizer);
        $entityManager->persist($incident);

        $discordIntegration = $this->createDiscordIntegration($larp, $organizer);
        $discordSource = ExternalSourceFactory::new()->with([
            'integration' => $discordIntegration,
            'kind' => ExternalSourceKind::DiscordChannel,
            'externalId' => '1100000000000000010',
            'title' => 'general',
            'config' => ['visibility' => 'organizer_only'],
        ])->create();
        ExternalItemFactory::new()->with(['source' => $discordSource, 'excerpt' => 'Secret plot excerpt'])->create();
        ExternalReferenceFactory::new()
            ->forStoryObject($character)
            ->createdBy($organizer)
            ->withProvider(LarpIntegrationProvider::Discord)
            ->with(['larp' => $larp])
            ->create();

        $googleIntegration = LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->google()->create();
        SharedFileFactory::new()->forIntegration($googleIntegration)->spreadsheet()->create();

        $entityManager->flush();

        return $continuedCharacter->getId();
    }

    private function prepareStoryObject(StoryObject $storyObject, Larp $larp, User $creator, string $title): void
    {
        $storyObject->setLarp($larp);
        $storyObject->setTitle($title);
        $storyObject->setCreatedBy($creator);
    }

    private function createCallRecordingWithAudio(FunctionalTester $I, Larp $larp, User $creator): string
    {
        $entityManager = $I->getEntityManager();
        $callRecording = new CallRecording($larp, 'Planning call', 'en');
        $callRecording->setCreatedBy($creator);
        $callRecording->addTrack(new CallRecordingTrack($callRecording, 'Organizer', 'organizer.flac', null));
        $entityManager->persist($callRecording);
        $entityManager->flush();

        /** @var CallRecordingStorage $storage */
        $storage = $I->grabService(CallRecordingStorage::class);
        file_put_contents($storage->pathFor($callRecording, 'organizer.flac'), 'audio');

        return $storage->recordingDirectory($callRecording);
    }

    private function createDiscordIntegration(mixed $larp, mixed $organizer): mixed
    {
        return LarpIntegrationFactory::new()
            ->forLarp($larp)
            ->createdBy($organizer)
            ->discord()
            ->botInstall(self::GUILD_ID)
            ->with(['config' => ['guildName' => 'Shadows of Ardenne LARP']])
            ->create();
    }

    private function persistedDiscordRouter(FunctionalTester $I): FixtureRouter
    {
        $discordRouter = $I->grabService('tests.integrations.discord.fixture_router');
        $I->persistService('tests.integrations.discord.fixture_router');

        return $discordRouter;
    }

    /**
     * @return list<string>
     */
    private function requestSummaries(FixtureRouter $discordRouter, int $sinceRequest): array
    {
        return array_map(
            static fn (array $request): string => $request['method'] . ' ' . $request['path'],
            array_slice($discordRouter->getRequests(), $sinceRequest),
        );
    }

    private function assertDeletionForbidden(FunctionalTester $I, mixed $user, Uuid $larpId): void
    {
        $I->amLoggedInAs($user);
        $I->sendAjaxPostRequest($this->deleteUrl($I, $larpId), ['_token' => 'irrelevant', 'confirmTitle' => self::LARP_TITLE]);

        $I->seeResponseCodeIs(403);
        $this->assertLarpExists($I, $larpId);
    }

    private function countRowsOfLarp(FunctionalTester $I, string $table, Uuid $larpId): int
    {
        return (int) $I->getEntityManager()->getConnection()->fetchOne(
            sprintf('SELECT COUNT(*) FROM %s WHERE larp_id = :id', $table),
            ['id' => $larpId->toRfc4122()],
        );
    }

    private function assertLarpExists(FunctionalTester $I, Uuid $larpId): void
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();
        $I->assertInstanceOf(Larp::class, $entityManager->getRepository(Larp::class)->find($larpId));
    }

    private function deleteUrl(FunctionalTester $I, Uuid $larpId): string
    {
        return $I->getUrl('backoffice_larp_delete', ['larp' => $larpId]);
    }
}
