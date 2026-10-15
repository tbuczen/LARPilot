<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Discord;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\Integrations\Entity\Enum\ConnectionType;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use Symfony\Component\Uid\Uuid;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\Integrations\ExternalItemFactory;
use Tests\Support\Factory\Integrations\ExternalReferenceFactory;
use Tests\Support\Factory\Integrations\ExternalSourceFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Http\FixtureRouter;
use Tests\Support\Http\IntegrationFixtureRouters;

class DiscordInstallCest
{
    private const CALLBACK_PATH = '/backoffice/larp/integration/connect/discord/check';
    private const GUILD_ID = IntegrationFixtureRouters::DISCORD_GUILD_ID;
    private const TEXT_CHANNEL_ID = '1100000000000000010';
    private const SECOND_TEXT_CHANNEL_ID = '1100000000000000210';
    private const FORUM_CHANNEL_ID = IntegrationFixtureRouters::DISCORD_FORUM_CHANNEL_ID;
    private const VOICE_CHANNEL_ID = '1100000000000000020';
    private const CATEGORY_CHANNEL_ID = '1100000000000000100';
    private const MAIN_PLOT_FORUM_TAG_ID = '1100000000000000901';
    private const SIDE_QUEST_FORUM_TAG_ID = '1100000000000000902';

    public function connectRedirectsToTheDiscordBotAuthorization(FunctionalTester $I): void
    {
        $I->wantTo('start inviting the Discord bot with read-only permissions and a signed state');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $authorizationQuery = $this->startDiscordConnect($I, $larp->getId());

        $I->assertSame('1100000000000000777', $authorizationQuery['client_id']);
        $I->assertSame('bot', $authorizationQuery['scope']);
        $I->assertSame('66560', $authorizationQuery['permissions']);
        $I->assertSame('0', $authorizationQuery['integration_type']);
        $I->assertSame('code', $authorizationQuery['response_type']);
        $I->assertSame(self::CALLBACK_PATH, parse_url($authorizationQuery['redirect_uri'], PHP_URL_PATH));
        $I->assertNotEmpty($authorizationQuery['state']);
        $I->assertStringNotContainsString($larp->getId()->toRfc4122(), $authorizationQuery['state']);
    }

    public function callbackStoresTheVerifiedGuildWithoutTokens(FunctionalTester $I): void
    {
        $I->wantTo('finish inviting the bot and store the verified server without keeping any token');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $discordRouter = $this->persistedDiscordRouter($I);
        $requestsBefore = count($discordRouter->getRequests());

        $I->amLoggedInAs($organizer);
        $state = $this->startDiscordConnect($I, $larp->getId())['state'];
        $this->visitDiscordCallback($I, ['state' => $state, 'code' => 'bot-code', 'guild_id' => self::GUILD_ID, 'permissions' => '66560']);

        $I->seeResponseCodeIsRedirection();
        $integration = $this->findDiscordIntegration($I, $larp->getId());
        $I->assertNotNull($integration);
        $I->assertSame(ConnectionType::BotInstall, $integration->getConnectionType());
        $I->assertSame(IntegrationStatus::Active, $integration->getStatus());
        $I->assertSame(self::GUILD_ID, $integration->getExternalAccountId());
        $I->assertSame('Shadows of Ardenne LARP', $integration->getConfig()['guildName'] ?? null);
        $I->assertSame('Shadows of Ardenne LARP', $integration->getConfig()['accountLabel'] ?? null);
        $I->assertNull($integration->getAccessToken());
        $I->assertNull($integration->getRefreshToken());

        $requests = $this->requestSummaries($discordRouter, $requestsBefore);
        $I->assertContains('POST /oauth2/token', $requests);
        $I->assertContains('POST /oauth2/token/revoke', $requests);
        $I->assertContains('GET /guilds/' . self::GUILD_ID, $requests);
        $I->assertCount(0, $this->sourcesOf($I, $larp->getId()));

        $I->followRedirect();
        $I->see('Discord is now connected.');
        $I->see('Connected to the Discord server "Shadows of Ardenne LARP".');
    }

    public function forgedStateIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('verify a forged Discord connect state pointing at another LARP is rejected');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $victimLarp = LarpFactory::createDraftLarp(UserFactory::createApprovedUser());

        $I->amLoggedInAs($organizer);
        $state = $this->startDiscordConnect($I, $larp->getId())['state'];
        [$payload, $signature] = explode('.', $state);
        $claims = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);
        $claims['larp'] = $victimLarp->getId()->toRfc4122();
        $forgedPayload = rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '=');

        $this->visitDiscordCallback($I, ['state' => $forgedPayload . '.' . $signature, 'code' => 'bot-code', 'guild_id' => self::GUILD_ID]);

        $I->seeResponseCodeIs(400);
        $I->assertNull($this->findDiscordIntegration($I, $victimLarp->getId()));
        $I->assertNull($this->findDiscordIntegration($I, $larp->getId()));
    }

    public function reusedStateIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('verify a Discord connect state cannot be replayed');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $state = $this->startDiscordConnect($I, $larp->getId())['state'];
        $this->visitDiscordCallback($I, ['state' => $state, 'code' => 'bot-code', 'guild_id' => self::GUILD_ID]);
        $I->seeResponseCodeIsRedirection();

        $this->visitDiscordCallback($I, ['state' => $state, 'code' => 'replayed-code', 'guild_id' => self::GUILD_ID]);

        $I->seeResponseCodeIs(400);
    }

    public function guildHintThatDiffersFromTheAuthorizedGuildIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('refuse a callback whose guild_id was swapped for another server');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $state = $this->startDiscordConnect($I, $larp->getId())['state'];
        $this->visitDiscordCallback($I, ['state' => $state, 'code' => 'bot-code', 'guild_id' => '1999999999999999999']);

        $I->seeResponseCodeIsRedirection();
        $I->assertNull($this->findDiscordIntegration($I, $larp->getId()));
        $I->followRedirect();
        $I->see('Could not connect Discord. Please try again.');
    }

    public function rejectedAuthorizationCodeStoresNothing(FunctionalTester $I): void
    {
        $I->wantTo('see a message when Discord rejects the authorization code');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $discordRouter = $this->persistedDiscordRouter($I)->on('POST', '/oauth2/token', 'invalid_grant.json', 400);

        try {
            $I->amLoggedInAs($organizer);
            $state = $this->startDiscordConnect($I, $larp->getId())['state'];
            $this->visitDiscordCallback($I, ['state' => $state, 'code' => 'stale-code', 'guild_id' => self::GUILD_ID]);

            $I->seeResponseCodeIsRedirection();
            $I->assertNull($this->findDiscordIntegration($I, $larp->getId()));
        } finally {
            $discordRouter->on('POST', '/oauth2/token', 'oauth2_token_bot.json');
        }
    }

    public function nonOrganizersAreForbidden(FunctionalTester $I): void
    {
        $I->wantTo('verify only organizers can connect, configure or disconnect Discord');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createDiscordIntegration($larp, $organizer);
        $storyWriter = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($storyWriter)->storyWriter()->create();

        $I->amLoggedInAs($storyWriter);
        $I->stopFollowingRedirects();

        $I->amOnRoute('backoffice_larp_connect_integration', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Discord->value]);
        $I->seeResponseCodeIs(403);

        $I->amOnRoute('backoffice_larp_discord_channels', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIs(403);

        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_discord_channels_save', ['larp' => $larp->getId()]), [
            'channels' => [self::TEXT_CHANNEL_ID => ['watched' => '1']],
        ]);
        $I->seeResponseCodeIs(403);

        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integrations_disconnect', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Discord->value]));
        $I->seeResponseCodeIs(403);

        $I->assertNotNull($this->findDiscordIntegration($I, $larp->getId()));
        $I->assertCount(0, $this->sourcesOf($I, $larp->getId()));
    }

    public function pickerListsTextAndForumChannelsAllUnwatched(FunctionalTester $I): void
    {
        $I->wantTo('see only text and forum channels in the picker, none of them watched yet');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createDiscordIntegration($larp, $organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_discord_channels', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        foreach ([self::TEXT_CHANNEL_ID, self::SECOND_TEXT_CHANNEL_ID, self::FORUM_CHANNEL_ID] as $channelId) {
            $I->seeElement(sprintf('tr[data-channel-id="%s"]', $channelId));
            $I->dontSeeCheckboxIsChecked(sprintf('input[name="channels[%s][watched]"]', $channelId));
            $I->seeOptionIsSelected(sprintf('select[name="channels[%s][visibility]"]', $channelId), 'Organizers only');
        }
        foreach ([self::VOICE_CHANNEL_ID, self::CATEGORY_CHANNEL_ID] as $channelId) {
            $I->dontSeeElement(sprintf('tr[data-channel-id="%s"]', $channelId));
        }
        $I->seeElement(sprintf('select[name="channels[%s][forumTags][%s][targetType]"]', self::FORUM_CHANNEL_ID, self::MAIN_PLOT_FORUM_TAG_ID));
    }

    public function unwatchedChannelNeverBecomesASource(FunctionalTester $I): void
    {
        $I->wantTo('save the picker and store only the watched channel');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createDiscordIntegration($larp, $organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_discord_channels', ['larp' => $larp->getId()]);
        $I->submitForm('form[name="discord_channels"]', [
            'channels' => [
                self::TEXT_CHANNEL_ID => ['watched' => '1', 'visibility' => 'player_facing', 'defaultTargetType' => 'thread'],
                self::SECOND_TEXT_CHANNEL_ID => ['visibility' => 'player_facing', 'defaultTargetType' => 'quest'],
            ],
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Discord channels saved.');
        $sources = $this->sourcesOf($I, $larp->getId());
        $I->assertCount(1, $sources);
        $I->assertSame(self::TEXT_CHANNEL_ID, $sources[0]->getExternalId());
        $I->assertSame(ExternalSourceKind::DiscordChannel, $sources[0]->getKind());
        $I->assertSame('general', $sources[0]->getTitle());
        $I->assertSame(sprintf('https://discord.com/channels/%s/%s', self::GUILD_ID, self::TEXT_CHANNEL_ID), $sources[0]->getUrl());
        $I->seeCheckboxIsChecked(sprintf('input[name="channels[%s][watched]"]', self::TEXT_CHANNEL_ID));
        $I->dontSeeCheckboxIsChecked(sprintf('input[name="channels[%s][watched]"]', self::SECOND_TEXT_CHANNEL_ID));
    }

    public function channelsUnknownToDiscordAreIgnored(FunctionalTester $I): void
    {
        $I->wantTo('verify a posted channel id the bot cannot see is never stored');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createDiscordIntegration($larp, $organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_discord_channels', ['larp' => $larp->getId()]);
        $I->submitForm('form[name="discord_channels"]', [
            'channels' => [
                self::VOICE_CHANNEL_ID => ['watched' => '1'],
                '1888888888888888888' => ['watched' => '1'],
            ],
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->assertCount(0, $this->sourcesOf($I, $larp->getId()));
    }

    public function pickerSavesVisibilityDefaultTypeAndForumTagMapping(FunctionalTester $I): void
    {
        $I->wantTo('store visibility, default story type and the forum tag mapping on the source');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createDiscordIntegration($larp, $organizer);
        $plotTag = $this->createTag($I, $larp->_real(), $organizer, 'Main plot');
        $foreignOrganizer = UserFactory::createApprovedUser();
        $foreignTag = $this->createTag($I, LarpFactory::createDraftLarp($foreignOrganizer)->_real(), $foreignOrganizer, 'Foreign');

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_discord_channels', ['larp' => $larp->getId()]);
        $I->submitForm('form[name="discord_channels"]', [
            'channels' => [
                self::FORUM_CHANNEL_ID => [
                    'watched' => '1',
                    'visibility' => 'player_facing',
                    'defaultTargetType' => 'character',
                    'forumTags' => [
                        self::MAIN_PLOT_FORUM_TAG_ID => ['tagId' => $plotTag, 'targetType' => 'thread'],
                        self::SIDE_QUEST_FORUM_TAG_ID => ['tagId' => $foreignTag, 'targetType' => 'quest'],
                    ],
                ],
            ],
        ]);

        $I->seeResponseCodeIsSuccessful();
        $sources = $this->sourcesOf($I, $larp->getId());
        $I->assertCount(1, $sources);
        $forumSource = $sources[0];
        $I->assertSame(ExternalSourceKind::DiscordForum, $forumSource->getKind());
        $I->assertSame('player_facing', $forumSource->getConfigValue('visibility'));
        $I->assertSame('character', $forumSource->getConfigValue('defaultTargetType'));
        $I->assertFalse($forumSource->getConfigValue('includePrivateThreads'));
        $I->assertSame(self::GUILD_ID, $forumSource->getConfigValue('guildId'));
        $I->assertSame([
            self::MAIN_PLOT_FORUM_TAG_ID => ['tagId' => $plotTag, 'targetType' => 'thread'],
            self::SIDE_QUEST_FORUM_TAG_ID => ['tagId' => null, 'targetType' => 'quest'],
        ], $forumSource->getConfigValue('forumTagMappings'));

        $I->seeOptionIsSelected(sprintf('select[name="channels[%s][visibility]"]', self::FORUM_CHANNEL_ID), 'Player-facing');
        $I->seeOptionIsSelected(sprintf('select[name="channels[%s][forumTags][%s][tagId]"]', self::FORUM_CHANNEL_ID, self::MAIN_PLOT_FORUM_TAG_ID), 'Main plot');
    }

    public function unwatchingAChannelRemovesItsSourceAndItems(FunctionalTester $I): void
    {
        $I->wantTo('unwatch a channel and drop everything synced from it');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createDiscordIntegration($larp, $organizer);
        $source = $this->createDiscordSource($integration, self::TEXT_CHANNEL_ID);
        ExternalItemFactory::new()->with(['source' => $source])->create();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_discord_channels', ['larp' => $larp->getId()]);
        $I->seeCheckboxIsChecked(sprintf('input[name="channels[%s][watched]"]', self::TEXT_CHANNEL_ID));
        $I->uncheckOption(sprintf('input[name="channels[%s][watched]"]', self::TEXT_CHANNEL_ID));
        $I->submitForm('form[name="discord_channels"]', []);

        $I->seeResponseCodeIsSuccessful();
        $I->assertCount(0, $this->sourcesOf($I, $larp->getId()));
        $I->assertSame(0, $I->getEntityManager()->getRepository(ExternalItem::class)->count([]));
    }

    public function eachLarpSeesOnlyItsOwnChannelsOnASharedServer(FunctionalTester $I): void
    {
        $I->wantTo('use one Discord server for two LARPs without sharing channel choices');

        $organizer = UserFactory::createApprovedUser();
        $firstLarp = LarpFactory::createDraftLarp($organizer);
        $secondLarp = LarpFactory::createDraftLarp($organizer);
        $firstIntegration = $this->createDiscordIntegration($firstLarp, $organizer);
        $this->createDiscordIntegration($secondLarp, $organizer);
        $this->createDiscordSource($firstIntegration, self::TEXT_CHANNEL_ID);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_discord_channels', ['larp' => $secondLarp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeCheckboxIsChecked(sprintf('input[name="channels[%s][watched]"]', self::TEXT_CHANNEL_ID));
        $I->assertCount(0, $this->sourcesOf($I, $secondLarp->getId()));
    }

    public function disconnectPurgesDataAndLeavesAnUnusedGuild(FunctionalTester $I): void
    {
        $I->wantTo('disconnect Discord, purge its data for the LARP and let the bot leave the unused server');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createDiscordIntegration($larp, $organizer);
        $source = $this->createDiscordSource($integration, self::TEXT_CHANNEL_ID);
        $item = ExternalItemFactory::new()->with(['source' => $source, 'excerpt' => 'Secret plot excerpt'])->create();
        $character = CharacterFactory::new()->with(['larp' => $larp])->create();
        ExternalReferenceFactory::new()
            ->forStoryObject($character)
            ->withProvider(LarpIntegrationProvider::Discord)
            ->with([
                'larp' => $larp,
                'externalId' => $item->getExternalId(),
                'referenceType' => ReferenceType::Thread,
                'source' => $source,
                'externalItem' => $item,
                'excerpt' => 'Secret plot excerpt',
            ])
            ->create();
        $discordRouter = $this->persistedDiscordRouter($I);
        $requestsBefore = count($discordRouter->getRequests());

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_sources', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Discord->value]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeElement(sprintf('a[href="%s"]', $I->getUrl('backoffice_larp_discord_channels', ['larp' => $larp->getId()->toRfc4122()])));
        $this->disconnect($I, $larp->getId());

        $I->seeResponseCodeIsRedirection();
        $I->assertNull($this->findDiscordIntegration($I, $larp->getId()));
        $entityManager = $I->getEntityManager();
        $I->assertSame(0, $entityManager->getRepository(ExternalSource::class)->count([]));
        $I->assertSame(0, $entityManager->getRepository(ExternalItem::class)->count([]));
        $references = $entityManager->getRepository(ExternalReference::class)->findBy(['larp' => $larp->getId()]);
        $I->assertCount(1, $references);
        $I->assertNull($references[0]->getExcerpt());
        $I->assertContains('DELETE /users/@me/guilds/' . self::GUILD_ID, $this->requestSummaries($discordRouter, $requestsBefore));
    }

    public function disconnectKeepsTheBotWhileAnotherLarpUsesTheGuild(FunctionalTester $I): void
    {
        $I->wantTo('disconnect one LARP and keep the bot on a server another LARP still uses');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $otherLarp = LarpFactory::createDraftLarp(UserFactory::createApprovedUser());
        $integration = $this->createDiscordIntegration($larp, $organizer);
        $otherIntegration = $this->createDiscordIntegration($otherLarp, $organizer);
        $this->createDiscordSource($integration, self::TEXT_CHANNEL_ID);
        $this->createDiscordSource($otherIntegration, self::TEXT_CHANNEL_ID);
        $discordRouter = $this->persistedDiscordRouter($I);
        $requestsBefore = count($discordRouter->getRequests());

        $I->amLoggedInAs($organizer);
        $this->disconnect($I, $larp->getId());

        $I->seeResponseCodeIsRedirection();
        $I->assertNull($this->findDiscordIntegration($I, $larp->getId()));
        $I->assertNotNull($this->findDiscordIntegration($I, $otherLarp->getId()));
        $I->assertCount(1, $this->sourcesOf($I, $otherLarp->getId()));
        $I->assertNotContains('DELETE /users/@me/guilds/' . self::GUILD_ID, $this->requestSummaries($discordRouter, $requestsBefore));
    }

    private function disconnect(FunctionalTester $I, Uuid $larpId): void
    {
        $I->amOnRoute('backoffice_larp_integrations_hub', ['larp' => $larpId]);
        $csrfToken = $I->grabValueFrom('#deleteModal input[name="_token"]');
        $I->stopFollowingRedirects();
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integrations_disconnect', [
            'larp' => $larpId->toRfc4122(),
            'provider' => LarpIntegrationProvider::Discord->value,
        ]), ['_token' => $csrfToken]);
    }

    /**
     * @return array<string, string>
     */
    private function startDiscordConnect(FunctionalTester $I, Uuid $larpId): array
    {
        $I->stopFollowingRedirects();
        $I->amOnRoute('backoffice_larp_connect_integration', [
            'larp' => $larpId->toRfc4122(),
            'provider' => LarpIntegrationProvider::Discord->value,
        ]);
        $I->seeResponseCodeIsRedirection();

        $authorizationUrl = (string) $I->grabResponse()->headers->get('Location');
        $I->assertStringStartsWith('https://discord.com/oauth2/authorize?', $authorizationUrl);
        parse_str((string) parse_url($authorizationUrl, PHP_URL_QUERY), $authorizationQuery);

        return $authorizationQuery;
    }

    /**
     * @param array<string, string> $query
     */
    private function visitDiscordCallback(FunctionalTester $I, array $query): void
    {
        $I->stopFollowingRedirects();
        $I->amOnPage(self::CALLBACK_PATH . '?' . http_build_query($query));
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
    private function requestSummaries(FixtureRouter $discordRouter, int $sinceRequest = 0): array
    {
        return array_map(
            static fn (array $request): string => $request['method'] . ' ' . $request['path'],
            array_slice($discordRouter->getRequests(), $sinceRequest),
        );
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

    private function createDiscordSource(mixed $integration, string $channelId): mixed
    {
        return ExternalSourceFactory::new()->with([
            'integration' => $integration,
            'kind' => ExternalSourceKind::DiscordChannel,
            'externalId' => $channelId,
            'title' => 'general',
            'config' => ['visibility' => 'organizer_only'],
        ])->create();
    }

    private function createTag(FunctionalTester $I, Larp $larp, User $creator, string $title): string
    {
        $tag = new Tag();
        $tag->setLarp($larp);
        $tag->setCreatedBy($creator);
        $tag->setTitle($title);
        $entityManager = $I->getEntityManager();
        $entityManager->persist($tag);
        $entityManager->flush();

        return $tag->getId()->toRfc4122();
    }

    /**
     * @return list<ExternalSource>
     */
    private function sourcesOf(FunctionalTester $I, Uuid $larpId): array
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();

        return $entityManager->getRepository(ExternalSource::class)->createQueryBuilder('source')
            ->join('source.integration', 'integration')
            ->andWhere('integration.larp = :larp')
            ->setParameter('larp', $larpId, 'uuid')
            ->orderBy('source.externalId', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function findDiscordIntegration(FunctionalTester $I, Uuid $larpId): ?LarpIntegration
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();

        return $entityManager->getRepository(LarpIntegration::class)->findOneBy([
            'larp' => $larpId,
            'provider' => LarpIntegrationProvider::Discord,
        ]);
    }
}
