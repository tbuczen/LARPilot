<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Discord;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Entity\Enum\ExternalItemReviewStatus;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalItemSuggestion;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Message\SyncExternalSourceMessage;
use App\Domain\Integrations\MessageHandler\SyncExternalSourceHandler;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\Integrations\Service\IntegrationRegistry;
use App\Domain\Integrations\Service\Suggestion\LinkSuggestionAggregator;
use App\Domain\Integrations\Service\Suggestion\MappedTagLinkSuggester;
use App\Domain\Integrations\Service\Suggestion\TitleMatchLinkSuggester;
use App\Domain\Integrations\Service\Sync\SourceSyncIntervalPolicy;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\Integrations\ExternalItemFactory;
use Tests\Support\Factory\Integrations\ExternalSourceFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\Factory\StoryObject\FactionFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Http\FixtureRouter;
use Tests\Support\Http\IntegrationFixtureRouters;
use Tests\Support\Integrations\MarkerLinkSuggester;
use Zenstruck\Foundry\Persistence\Proxy;

class DiscordInboxCest
{
    private const NOW = '2026-10-12 12:00:00';
    private const GUILD_ID = IntegrationFixtureRouters::DISCORD_GUILD_ID;
    private const FORUM_CHANNEL_ID = IntegrationFixtureRouters::DISCORD_FORUM_CHANNEL_ID;
    private const SEAL_THREAD_ID = IntegrationFixtureRouters::DISCORD_THREAD_ID;
    private const MAIN_PLOT_FORUM_TAG_ID = '1100000000000000901';
    private const BULK_FORM = 'form[name="discord_inbox_bulk"]';

    public function _before(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
        $this->routeDiscordFixtures($I);
    }

    private function routeDiscordFixtures(FunctionalTester $I): void
    {
        $this->integrationsTransport($I)->reset();
        $this->discordRouter($I)
            ->on('GET', '/guilds/{guild_id}/channels', 'channels.json')
            ->on('GET', '/guilds/{guild_id}/threads/active', 'threads_active.json')
            ->on('GET', '/channels/{channel_id}/threads/archived/public', 'threads_archived_public.json')
            ->on('GET', '/channels/{channel_id}/threads/archived/public?before=' . rawurlencode('2026-07-30T08:00:00.000+00:00'), 'threads_archived_public_last_page.json')
            ->on('GET', '/channels/{channel_id}/messages', 'messages.json');
    }

    public function syncedFixtureThreadGetsNameAndTagSuggestions(FunctionalTester $I): void
    {
        $I->wantTo('see a synced forum post suggested for the character its title names and the type and tag its forum tag maps to');

        [$organizer, $larp, $tag] = $this->createLarpWithTag($I);
        $morwen = CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->withTitle('Lady Morwen')->create();
        CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->withTitle('Aldric')->create();
        $source = $this->createForumSource($larp, $organizer, $tag);

        $this->syncSource($I, $source);

        $sealPost = $this->findItem($I, $source, self::SEAL_THREAD_ID);
        $suggestions = $sealPost->getSuggestions()->toArray();
        $I->assertNotNull($sealPost->getSuggestionsRefreshedAt());
        $I->assertCount(2, $suggestions);

        $nameSuggestion = $this->suggestionFrom($suggestions, TitleMatchLinkSuggester::SOURCE);
        $I->assertTrue($nameSuggestion->getStoryObject()?->getId()->equals($morwen->getId()));

        $tagSuggestion = $this->suggestionFrom($suggestions, MappedTagLinkSuggester::SOURCE);
        $I->assertSame(TargetType::Character, $tagSuggestion->getTargetType());
        $I->assertTrue($tagSuggestion->getTag()?->getId()->equals($tag->getId()));
        $I->assertNull($tagSuggestion->getStoryObject());
    }

    public function titleMatchIsNormalizedAndAccentFolded(FunctionalTester $I): void
    {
        $I->wantTo('match thread titles against story object titles ignoring case, punctuation and accents, on whole words only');

        [$organizer, $larp, $source] = $this->createInbox();
        $wolves = FactionFactory::new()->forLarp($larp)->withTitle('Wolves')->create(['createdBy' => $organizer]);
        FactionFactory::new()->forLarp($larp)->withTitle('Wolf')->create(['createdBy' => $organizer]);
        $zoe = CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->withTitle('Zoë Żółwińska')->create();
        $betrayal = $this->createItem($source, 'Wolves – betrayal');
        $diary = $this->createItem($source, 'ZOE ZOLWINSKA: diary');

        $this->refreshSuggestions($I, $larp);

        $betrayalSuggestions = $this->reloadItem($I, $betrayal)->getLinkSuggestions();
        $I->assertCount(1, $betrayalSuggestions);
        $I->assertTrue($betrayalSuggestions[0]->getStoryObject()?->getId()->equals($wolves->getId()));
        $I->assertInstanceOf(Faction::class, $betrayalSuggestions[0]->getStoryObject());

        $diarySuggestions = $this->reloadItem($I, $diary)->getLinkSuggestions();
        $I->assertCount(1, $diarySuggestions);
        $I->assertTrue($diarySuggestions[0]->getStoryObject()?->getId()->equals($zoe->getId()));
    }

    public function unchangedSuggestionsAreKeptAndStaleOnesReplaced(FunctionalTester $I): void
    {
        $I->wantTo('keep stored suggestions when nothing changed and replace them when the story changes');

        [$organizer, $larp, $source] = $this->createInbox();
        FactionFactory::new()->forLarp($larp)->withTitle('Wolves')->create(['createdBy' => $organizer]);
        $item = $this->createItem($source, 'Wolves and Ravens');

        $this->refreshSuggestions($I, $larp);
        $firstSuggestionIds = $this->suggestionIds($this->reloadItem($I, $item));
        $this->refreshSuggestions($I, $larp);
        $I->assertSame($firstSuggestionIds, $this->suggestionIds($this->reloadItem($I, $item)));

        $ravens = FactionFactory::new()->forLarp($larp)->withTitle('Ravens')->create(['createdBy' => $organizer]);
        $this->refreshSuggestions($I, $larp);
        $refreshedItem = $this->reloadItem($I, $item);
        $I->assertCount(2, $refreshedItem->getLinkSuggestions());
        $I->assertContains($ravens->getId()->toRfc4122(), array_map(
            static fn (ExternalItemSuggestion $suggestion): ?string => $suggestion->getStoryObject()?->getId()->toRfc4122(),
            $refreshedItem->getLinkSuggestions(),
        ));
    }

    public function taggedServiceIsEnoughToAddASuggester(FunctionalTester $I): void
    {
        $I->wantTo('see suggestions from a test-only suggester registered with nothing but the suggester tag');

        [, $larp, $source] = $this->createInbox();
        $markedItem = $this->createItem($source, 'Lost relic ' . MarkerLinkSuggester::MARKER);
        $plainItem = $this->createItem($source, 'Lost relic');

        $this->refreshSuggestions($I, $larp);

        $markedSuggestions = $this->reloadItem($I, $markedItem)->getSuggestions()->toArray();
        $I->assertCount(1, $markedSuggestions);
        $I->assertSame(MarkerLinkSuggester::SOURCE, $markedSuggestions[0]->getSource());
        $I->assertSame(TargetType::Item, $markedSuggestions[0]->getTargetType());
        $I->assertEqualsWithDelta(MarkerLinkSuggester::SCORE, $markedSuggestions[0]->getScore(), 0.0001);
        $I->assertCount(0, $this->reloadItem($I, $plainItem)->getSuggestions());
    }

    public function ignoredThreadStaysIgnoredAfterResync(FunctionalTester $I): void
    {
        $I->wantTo('ignore a synced thread in the inbox and see it stay ignored after the next sync');

        [$organizer, $larp, $tag] = $this->createLarpWithTag($I);
        $source = $this->createForumSource($larp, $organizer, $tag);
        $this->syncSource($I, $source);
        $sealPost = $this->findItem($I, $source, self::SEAL_THREAD_ID);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsSuccessful();
        $I->see('Lady Morwen — the stolen seal');
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_discord_inbox_ignore', ['larp' => $larp->getId()]), [
            '_token' => $this->grabToken($I),
            'items' => [$sealPost->getId()->toRfc4122()],
        ]);
        $I->seeResponseCodeIsRedirection();
        $I->assertSame(ExternalItemReviewStatus::Ignored, $this->findItem($I, $source, self::SEAL_THREAD_ID)->getReviewStatus());

        $this->routeDiscordFixtures($I);
        $this->syncSource($I, $this->findSource($I, $source));

        $resyncedPost = $this->findItem($I, $source, self::SEAL_THREAD_ID);
        $I->assertSame(ExternalItemReviewStatus::Ignored, $resyncedPost->getReviewStatus());
    }

    public function createFromThreadCreatesObjectAndLink(FunctionalTester $I): void
    {
        $I->wantTo('create a character from a synced forum post and see it linked, titled, described and tagged from the thread');

        [$organizer, $larp, $tag] = $this->createLarpWithTag($I);
        $source = $this->createForumSource($larp, $organizer, $tag);
        $this->syncSource($I, $source);
        $sealPost = $this->findItem($I, $source, self::SEAL_THREAD_ID);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId()]);
        $I->seeElement('button[data-bs-target="#inboxCreateModal"][data-item-id="' . $sealPost->getId()->toRfc4122() . '"][data-target-type="character"]');
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_discord_inbox_create', ['larp' => $larp->getId()]), [
            '_token' => $this->grabToken($I),
            'item' => $sealPost->getId()->toRfc4122(),
            'targetType' => TargetType::Character->value,
        ]);
        $I->seeResponseCodeIsRedirection();

        $I->getEntityManager()->clear();
        $character = $I->getEntityManager()->getRepository(Character::class)->findOneBy(['larp' => $larp->getId(), 'title' => 'Lady Morwen — the stolen seal']);
        $I->assertInstanceOf(Character::class, $character);
        $I->assertStringContainsString('<p>Lady Morwen wants the stolen seal back before the council convenes.', (string) $character->getDescription());
        $I->assertTrue($character->getCreatedBy()?->getId()->equals($organizer->getId()));
        $I->assertSame(['Main plot'], array_map(static fn (Tag $characterTag): ?string => $characterTag->getTitle(), $character->getTags()->toArray()));

        $references = $character->getExternalReferences()->toArray();
        $I->assertCount(1, $references);
        $I->assertSame(self::SEAL_THREAD_ID, $references[0]->getExternalId());
        $I->assertSame(ReferenceRole::Primary, $references[0]->getRole());
        $I->assertSame(ExternalItemReviewStatus::Linked, $this->findItem($I, $source, self::SEAL_THREAD_ID)->getReviewStatus());
    }

    public function createFromThreadRejectsUnsupportedType(FunctionalTester $I): void
    {
        $I->wantTo('see creating from a thread refused for a type the inbox does not offer');

        [$organizer, $larp, $source] = $this->createInbox();
        $item = $this->createItem($source, 'Some relation');

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId()]);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_discord_inbox_create', ['larp' => $larp->getId()]), [
            '_token' => $this->grabToken($I),
            'item' => $item->getId()->toRfc4122(),
            'targetType' => TargetType::Relation->value,
        ]);
        $I->seeResponseCodeIsRedirection();
        $I->assertSame(ExternalItemReviewStatus::Unreviewed, $this->reloadItem($I, $item)->getReviewStatus());
        $I->assertSame(0, $I->getEntityManager()->getRepository(StoryObject::class)->count(['larp' => $larp->getId()]));
    }

    public function bulkLinkAndBulkIgnore(FunctionalTester $I): void
    {
        $I->wantTo('link two threads to one faction and ignore two others in bulk');

        [$organizer, $larp, $source] = $this->createInbox();
        $faction = FactionFactory::new()->forLarp($larp)->withTitle('Wolves')->create(['createdBy' => $organizer]);
        $firstLinked = $this->createItem($source, 'Hunt planning');
        $secondLinked = $this->createItem($source, 'Pack hierarchy');
        $firstIgnored = $this->createItem($source, 'Off-topic chatter');
        $secondIgnored = $this->createItem($source, 'Car pooling');
        $untouched = $this->createItem($source, 'Costume questions');

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId()]);
        $token = $this->grabToken($I);

        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_discord_inbox_link', ['larp' => $larp->getId()]), [
            '_token' => $token,
            'storyObject' => $faction->getId()->toRfc4122(),
            'items' => [$firstLinked->getId()->toRfc4122(), $secondLinked->getId()->toRfc4122()],
        ]);
        $I->seeResponseCodeIsRedirection();

        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_discord_inbox_ignore', ['larp' => $larp->getId()]), [
            '_token' => $token,
            'items' => [$firstIgnored->getId()->toRfc4122(), $secondIgnored->getId()->toRfc4122()],
        ]);
        $I->seeResponseCodeIsRedirection();

        $I->assertSame(ExternalItemReviewStatus::Linked, $this->reloadItem($I, $firstLinked)->getReviewStatus());
        $I->assertSame(ExternalItemReviewStatus::Linked, $this->reloadItem($I, $secondLinked)->getReviewStatus());
        $I->assertSame(ExternalItemReviewStatus::Ignored, $this->reloadItem($I, $firstIgnored)->getReviewStatus());
        $I->assertSame(ExternalItemReviewStatus::Ignored, $this->reloadItem($I, $secondIgnored)->getReviewStatus());
        $I->assertSame(ExternalItemReviewStatus::Unreviewed, $this->reloadItem($I, $untouched)->getReviewStatus());
        $I->assertSame(2, $I->getEntityManager()->getRepository(ExternalReference::class)->count(['storyObject' => $faction->getId()]));

        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId()]);
        $I->see('Costume questions');
        $I->dontSee('Hunt planning');
        $I->dontSee('Car pooling');
        $I->see('1', 'a.nav-link[href$="/integration/discord/inbox"] [data-inbox-count]');

        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId(), 'inbox_filter' => ['status' => 'ignored']]);
        $I->see('Car pooling');
        $I->see('Off-topic chatter');
        $I->dontSee('Costume questions');

        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_discord_inbox_restore', ['larp' => $larp->getId()]), [
            '_token' => $token,
            'items' => [$secondIgnored->getId()->toRfc4122()],
        ]);
        $I->assertSame(ExternalItemReviewStatus::Unreviewed, $this->reloadItem($I, $secondIgnored)->getReviewStatus());
    }

    public function filtersByChannelAndSuggestions(FunctionalTester $I): void
    {
        $I->wantTo('filter the inbox by channel and by whether a thread has suggestions');

        [$organizer, $larp, $source] = $this->createInbox();
        $otherSource = ExternalSourceFactory::new()->create([
            'integration' => $source->getIntegration(),
            'kind' => ExternalSourceKind::DiscordChannel,
            'externalId' => '1100000000000000777',
            'title' => 'gm-chat',
        ]);
        FactionFactory::new()->forLarp($larp)->withTitle('Wolves')->create(['createdBy' => $organizer]);
        $this->createItem($source, 'Wolves – betrayal');
        $this->createItem($source, 'Weather forecast');
        $this->createItem($otherSource, 'Rules lawyering');

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId()]);
        $I->see('Wolves – betrayal');
        $I->see('Wolves · Faction', '[data-inbox-suggestions] button[data-suggestion-source="name"]');

        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId(), 'inbox_filter' => ['status' => 'unreviewed', 'hasSuggestion' => 'yes']]);
        $I->see('Wolves – betrayal');
        $I->dontSee('Weather forecast');
        $I->dontSee('Rules lawyering');

        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId(), 'inbox_filter' => ['status' => 'unreviewed', 'hasSuggestion' => 'no']]);
        $I->dontSee('Wolves – betrayal');
        $I->see('Weather forecast');

        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId(), 'inbox_filter' => ['channel' => $otherSource->getId()->toRfc4122(), 'status' => 'unreviewed']]);
        $I->see('Rules lawyering');
        $I->dontSee('Weather forecast');
    }

    public function storyWriterCanTriageButPlayerCannot(FunctionalTester $I): void
    {
        $I->wantTo('let story writers triage the inbox and keep players out');

        [, $larp, $source] = $this->createInbox();
        $item = $this->createItem($source, 'Secret plot');
        $storyWriter = $this->createParticipant($larp, 'storyWriter');
        $player = $this->createParticipant($larp, 'player');

        $I->amLoggedInAs($storyWriter);
        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsSuccessful();
        $I->see('Secret plot');
        $I->dontSeeElement('.integration-tabs');
        $I->seeElement('a[data-menu-discord-inbox]');
        $token = $this->grabToken($I);

        $I->amLoggedInAs($player);
        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(403);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_discord_inbox_ignore', ['larp' => $larp->getId()]), [
            '_token' => $token,
            'items' => [$item->getId()->toRfc4122()],
        ]);
        $I->seeResponseCodeIs(403);
        $I->amOnRoute('backoffice_larp_discord_inbox_story_objects', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(403);
        $I->assertSame(ExternalItemReviewStatus::Unreviewed, $this->reloadItem($I, $item)->getReviewStatus());

        $I->amLoggedInAs($storyWriter);
        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId()]);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_discord_inbox_ignore', ['larp' => $larp->getId()]), [
            '_token' => $this->grabToken($I),
            'items' => [$item->getId()->toRfc4122()],
        ]);
        $I->seeResponseCodeIsRedirection();
        $I->assertSame(ExternalItemReviewStatus::Ignored, $this->reloadItem($I, $item)->getReviewStatus());
    }

    public function actionsRequireValidCsrfTokenAndItemsOfTheLarp(FunctionalTester $I): void
    {
        $I->wantTo('see triage actions refused without a valid CSRF token and unable to touch another LARP\'s threads');

        [$organizer, $larp, $source] = $this->createInbox();
        $item = $this->createItem($source, 'Guarded thread');
        [$otherOrganizer, $otherLarp] = $this->createInbox();
        $foreignItem = $this->createItem($source, 'Foreign thread');

        $I->amLoggedInAs($organizer);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_discord_inbox_ignore', ['larp' => $larp->getId()]), [
            '_token' => 'invalid',
            'items' => [$item->getId()->toRfc4122()],
        ]);
        $I->seeResponseCodeIs(403);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_discord_inbox_create', ['larp' => $larp->getId()]), [
            'item' => $item->getId()->toRfc4122(),
            'targetType' => TargetType::Faction->value,
        ]);
        $I->seeResponseCodeIs(403);
        $I->assertSame(ExternalItemReviewStatus::Unreviewed, $this->reloadItem($I, $item)->getReviewStatus());

        $I->amLoggedInAs($otherOrganizer);
        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $otherLarp->getId()]);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_discord_inbox_ignore', ['larp' => $otherLarp->getId()]), [
            '_token' => $this->grabToken($I),
            'items' => [$foreignItem->getId()->toRfc4122()],
        ]);
        $I->seeResponseCodeIsRedirection();
        $I->assertSame(ExternalItemReviewStatus::Unreviewed, $this->reloadItem($I, $foreignItem)->getReviewStatus());
    }

    public function inboxIsReachedFromHubWithCountBadge(FunctionalTester $I): void
    {
        $I->wantTo('reach the inbox from the Discord hub card and tab, each showing the unreviewed count');

        [$organizer, $larp, $source] = $this->createInbox();
        $this->createItem($source, 'First thread');
        $this->createItem($source, 'Second thread');
        $this->createItem($source, 'Already ignored', ExternalItemReviewStatus::Ignored);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_hub', ['larp' => $larp->getId()]);
        $I->see('2', '[data-provider="discord"] a[data-inbox-link] [data-inbox-count]');

        $I->amOnRoute('backoffice_larp_integrations_sources', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Discord->value]);
        $I->see('Inbox', '.integration-tabs a.nav-link');
        $I->see('2', '.integration-tabs [data-inbox-count]');

        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('.integration-tabs a.nav-link.active[href$="/integration/discord/inbox"]');
    }

    public function inboxIsNotFoundWithoutDiscord(FunctionalTester $I): void
    {
        $I->wantTo('see the inbox missing for a LARP without Discord');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_discord_inbox', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(404);
    }

    /**
     * @return array{0: User|Proxy, 1: Larp|Proxy, 2: ExternalSource|Proxy}
     */
    private function createInbox(): array
    {
        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = LarpIntegrationFactory::new()
            ->forLarp($larp)
            ->createdBy($organizer)
            ->discord()
            ->botInstall('guild-' . bin2hex(random_bytes(4)))
            ->create();
        $source = ExternalSourceFactory::new()->create([
            'integration' => $integration,
            'kind' => ExternalSourceKind::DiscordForum,
            'externalId' => (string) random_int(1_000_000_000, 9_999_999_999),
            'title' => 'story-forum',
            'config' => ['visibility' => 'organizer_only'],
        ]);

        return [$organizer, $larp, $source];
    }

    /**
     * @return array{0: User|Proxy, 1: Larp|Proxy, 2: Tag}
     */
    private function createLarpWithTag(FunctionalTester $I): array
    {
        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $entityManager = $I->getEntityManager();
        $tag = new Tag();
        $tag->setLarp($entityManager->find(Larp::class, $larp->getId()));
        $tag->setCreatedBy($entityManager->find(User::class, $organizer->getId()));
        $tag->setTitle('Main plot');
        $entityManager->persist($tag);
        $entityManager->flush();

        return [$organizer, $larp, $tag];
    }

    private function createForumSource(Larp|Proxy $larp, User|Proxy $organizer, Tag $tag): ExternalSource
    {
        $integration = LarpIntegrationFactory::new()
            ->forLarp($larp)
            ->createdBy($organizer)
            ->discord()
            ->botInstall(self::GUILD_ID)
            ->create();

        return ExternalSourceFactory::new()->create([
            'integration' => $integration,
            'kind' => ExternalSourceKind::DiscordForum,
            'externalId' => self::FORUM_CHANNEL_ID,
            'title' => 'character-threads',
            'config' => [
                'visibility' => 'organizer_only',
                'defaultTargetType' => null,
                'forumTagMappings' => [
                    self::MAIN_PLOT_FORUM_TAG_ID => ['tagId' => $tag->getId()->toRfc4122(), 'targetType' => 'character'],
                ],
                'includePrivateThreads' => false,
            ],
        ])->_real();
    }

    private function createItem(
        ExternalSource|Proxy $source,
        string $title,
        ExternalItemReviewStatus $reviewStatus = ExternalItemReviewStatus::Unreviewed,
    ): ExternalItem|Proxy {
        return ExternalItemFactory::new()->create([
            'source' => $source,
            'kind' => ExternalItemKind::ForumPost,
            'externalId' => (string) random_int(1_000_000_000, 9_999_999_999),
            'title' => $title,
            'excerpt' => 'Excerpt of ' . $title,
            'reviewStatus' => $reviewStatus,
        ]);
    }

    private function createParticipant(Larp|Proxy $larp, string $role): User
    {
        $user = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($user)
            ->{$role}()
            ->create();

        return $user instanceof Proxy ? $user->_real() : $user;
    }

    private function refreshSuggestions(FunctionalTester $I, Larp|Proxy $larp): void
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();
        $realLarp = $entityManager->find(Larp::class, $larp->getId());
        $items = $entityManager->getRepository(ExternalItem::class)->createQueryBuilder('item')
            ->innerJoin('item.source', 'source')
            ->innerJoin('source.integration', 'integration')
            ->andWhere('integration.larp = :larp')
            ->setParameter('larp', $realLarp)
            ->getQuery()
            ->getResult();

        $I->grabService(LinkSuggestionAggregator::class)->refresh($realLarp, $items);
    }

    /**
     * @param list<ExternalItemSuggestion> $suggestions
     */
    private function suggestionFrom(array $suggestions, string $source): ExternalItemSuggestion
    {
        foreach ($suggestions as $suggestion) {
            if ($suggestion->getSource() === $source) {
                return $suggestion;
            }
        }

        throw new \RuntimeException(sprintf('No suggestion from "%s".', $source));
    }

    /**
     * @return list<string>
     */
    private function suggestionIds(ExternalItem $item): array
    {
        $suggestionIds = array_map(
            static fn (ExternalItemSuggestion $suggestion): string => $suggestion->getId()->toRfc4122(),
            $item->getSuggestions()->toArray(),
        );
        sort($suggestionIds);

        return $suggestionIds;
    }

    private function grabToken(FunctionalTester $I): string
    {
        return $I->grabValueFrom(self::BULK_FORM . ' input[name="_token"]');
    }

    private function syncSource(FunctionalTester $I, ExternalSource $source): void
    {
        $handler = new SyncExternalSourceHandler(
            $I->grabService(ExternalSourceRepository::class),
            $I->grabService(ExternalItemRepository::class),
            $I->grabService(IntegrationRegistry::class),
            new SourceSyncIntervalPolicy(),
            $I->grabService(EntityManagerInterface::class),
            $I->grabService(LockFactory::class),
            $I->grabService(MessageBusInterface::class),
            new MockClock(self::NOW),
            new NullLogger(),
            $I->grabService(LinkSuggestionAggregator::class),
        );
        $transport = $this->integrationsTransport($I);
        $transport->reset();

        $handler(new SyncExternalSourceMessage($source->getId()));

        $handledMessages = 0;
        while (($nextEnvelope = $transport->getSent()[$handledMessages] ?? null) !== null) {
            $handledMessages++;
            $handler($nextEnvelope->getMessage());
        }
    }

    private function findSource(FunctionalTester $I, ExternalSource $source): ExternalSource
    {
        $I->getEntityManager()->clear();

        return $I->getEntityManager()->find(ExternalSource::class, $source->getId());
    }

    private function findItem(FunctionalTester $I, ExternalSource $source, string $externalId): ExternalItem
    {
        $I->getEntityManager()->clear();

        return $I->grabService(ExternalItemRepository::class)->findOneBy(['source' => $source->getId(), 'externalId' => $externalId]);
    }

    private function reloadItem(FunctionalTester $I, ExternalItem|Proxy $item): ExternalItem
    {
        $I->getEntityManager()->clear();

        return $I->getEntityManager()->find(ExternalItem::class, $item->getId());
    }

    private function discordRouter(FunctionalTester $I): FixtureRouter
    {
        return $I->grabService('tests.integrations.discord.fixture_router');
    }

    private function integrationsTransport(FunctionalTester $I): InMemoryTransport
    {
        return $I->grabService('messenger.transport.integrations');
    }
}
