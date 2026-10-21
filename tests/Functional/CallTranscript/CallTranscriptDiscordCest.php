<?php

declare(strict_types=1);

namespace Tests\Functional\CallTranscript;

use App\Domain\CallTranscript\Entity\Enum\CallOutput;
use App\Domain\CallTranscript\Entity\Enum\CallOutputStatus;
use App\Domain\CallTranscript\Entity\Enum\CallRecordingStatus;
use App\Domain\Integrations\Discord\DiscordBotPermissions;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceVisibility;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use Tests\Support\CallTranscript\CallTranscriptFlow;
use Tests\Support\CallTranscript\FakeLlmProvider;
use Tests\Support\CallTranscript\FakeSpeechToTextProvider;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\ExternalSourceFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Http\FixtureRouter;
use Tests\Support\Http\IntegrationFixtureRouters;

class CallTranscriptDiscordCest
{
    use CallTranscriptFlow;

    private const ORGANIZER_CHANNEL_ID = '1100000000000000210';
    private const PLAYER_FORUM_ID = '1100000000000000200';
    private const THREAD_ID = '1100000000000000555';

    private ?FixtureRouter $discordRouter = null;

    private int $requestsBefore = 0;

    public function _before(FunctionalTester $I): void
    {
        FakeSpeechToTextProvider::reset();
        FakeLlmProvider::reset();
        $this->discordRouter = $I->grabService('tests.integrations.discord.fixture_router');
        $I->persistService('tests.integrations.discord.fixture_router');
        $this->requestsBefore = count($this->discordRouter->getRequests());
    }

    public function summaryIsPostedInAThreadOfTheChosenChannel(FunctionalTester $I): void
    {
        $I->wantTo('post the summary into a new thread of the chosen organizer-only Discord channel');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->connectDiscord($larp);
        FakeLlmProvider::respondWith([
            'summary' => str_repeat('Długie podsumowanie rozmowy. ', 60),
            'decisions' => array_map(static fn (int $index): string => sprintf('Decyzja numer %d o frakcjach i wątkach', $index), range(1, 30)),
        ]);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
        $I->seeElement(sprintf('optgroup[label="Organizers only"] option[value="%s"]', self::ORGANIZER_CHANNEL_ID));
        $I->seeElement(sprintf('optgroup[label="Player-facing – players will see the post"] option[value="%s"]', self::PLAYER_FORUM_ID));
        $I->seeOptionIsSelected('call_recording_upload[discordChannel]', "Don't post to Discord");

        $this->upload($I, $larp->getId()->toRfc4122(), 'Planowanie aktu 2', ['1-anna.flac' => '1|Działa'], options: [
            'summary' => true,
            'discordChannel' => self::ORGANIZER_CHANNEL_ID,
        ]);
        $this->startTranscription($I, 1);

        $recording = $this->findRecording($I, 'Planowanie aktu 2');
        $I->assertSame(CallRecordingStatus::COMPLETED, $recording->getStatus());
        $I->assertSame(CallOutputStatus::DONE, $recording->getDiscordStatus());
        $I->assertSame(self::THREAD_ID, $recording->getDiscordThreadId());
        $I->assertSame(sprintf('https://discord.com/channels/%s/%s', IntegrationFixtureRouters::DISCORD_GUILD_ID, self::THREAD_ID), $recording->getDiscordThreadUrl());

        $posts = $this->posts($this->discordRouter($I));
        $I->assertSame(sprintf('POST /channels/%s/threads', self::ORGANIZER_CHANNEL_ID), $posts[0]['request']);
        $I->assertSame(['name' => 'Planowanie aktu 2', 'type' => 11, 'auto_archive_duration' => 10080], $posts[0]['json']);
        $messages = array_slice($posts, 1);
        $I->assertGreaterThan(1, count($messages));
        foreach ($messages as $message) {
            $I->assertSame(sprintf('POST /channels/%s/messages', self::THREAD_ID), $message['request']);
            $I->assertLessThanOrEqual(2000, mb_strlen($message['json']['content']));
            $I->assertSame(['parse' => []], $message['json']['allowed_mentions']);
        }
        $I->assertStringStartsWith('**Podsumowanie**', $messages[0]['json']['content']);
        $lastMessage = $messages[array_key_last($messages)]['json']['content'];
        $I->assertStringContainsString('Decyzja numer 30', $lastMessage);
        $I->assertStringContainsString(sprintf(
            'Transkrypcja w LARPilot: <http://localhost/backoffice/larp/%s/knowledge/%s>',
            $larp->getId()->toRfc4122(),
            $recording->getKnowledgeDocument()?->getId()->toRfc4122(),
        ), $lastMessage);

        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
        $I->seeElement('[data-call-output="discord"][data-call-output-status="done"]');
    }

    public function nothingIsPostedWhenNoChannelIsChosen(FunctionalTester $I): void
    {
        $I->wantTo('not contact Discord when no channel is chosen for the upload');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->connectDiscord($larp);

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Quiet call', ['1-anna.flac' => '1|Cicho']);
        $this->startTranscription($I, 1);

        $I->assertSame(CallOutputStatus::SKIPPED, $this->findRecording($I, 'Quiet call')->getDiscordStatus());
        $I->assertSame([], $this->posts($this->discordRouter($I)));
    }

    public function forbiddenPostIsReportedAndPromptsForNewPermissions(FunctionalTester $I): void
    {
        $I->wantTo('keep the transcript when the bot may not post, ask to update its permissions and retry');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->connectDiscord($larp);

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Forbidden', ['1-anna.flac' => '1|Działa'], options: ['discordChannel' => self::ORGANIZER_CHANNEL_ID]);
        $this->discordRouter($I)->on('POST', '/channels/{channel_id}/threads', 'missing_access.json', 403);
        $this->startTranscription($I, 1);
        $this->discordRouter($I)->on('POST', '/channels/{channel_id}/threads', 'thread_created.json', 201);

        $recording = $this->findRecording($I, 'Forbidden');
        $I->assertSame(CallRecordingStatus::COMPLETED, $recording->getStatus());
        $I->assertSame('<p>[00:00:01] anna: Działa</p>', $recording->getKnowledgeDocument()?->getContent());
        $I->assertSame(CallOutputStatus::FAILED, $recording->getDiscordStatus());
        $I->assertStringContainsString('Update bot permissions', (string) $recording->outputFailureReason(CallOutput::DISCORD));
        $integration = $I->grabEntityFromRepository(LarpIntegration::class, ['larp' => $larp->_real(), 'provider' => LarpIntegrationProvider::Discord]);
        $I->assertTrue($integration->getConfig()[DiscordBotPermissions::POSTING_DENIED_CONFIG_KEY] ?? false);

        $I->amOnRoute('backoffice_larp_integrations_sources', ['larp' => $larp->getId(), 'provider' => 'discord']);
        $I->seeElement('[data-discord-permissions-prompt]');
        $I->see('Update bot permissions');

        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
        $this->submitAndDrain($I, 'form[name="call_recording_retry_discord"]');

        $I->assertSame(CallOutputStatus::DONE, $this->findRecording($I, 'Forbidden')->getDiscordStatus());
    }

    public function oldInviteShowsUpdatePermissionsPrompt(FunctionalTester $I): void
    {
        $I->wantTo('see an "Update bot permissions" prompt for a server connected with the old read-only invite');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        LarpIntegrationFactory::new()->discord()->botInstall(IntegrationFixtureRouters::DISCORD_GUILD_ID)->forLarp($larp)->create([
            'config' => ['guildName' => 'Shadows of Ardenne LARP'],
        ]);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_sources', ['larp' => $larp->getId(), 'provider' => 'discord']);
        $I->seeElement('[data-discord-permissions-prompt] a[href$="/integration/connect/discord"]');

        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
        $I->dontSeeElement('[name="call_recording_upload[discordChannel]"]');
    }

    public function newInviteHasNoPrompt(FunctionalTester $I): void
    {
        $I->wantTo('see no permissions prompt when the bot was invited with posting permissions');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->connectDiscord($larp);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_sources', ['larp' => $larp->getId(), 'provider' => 'discord']);
        $I->dontSeeElement('[data-discord-permissions-prompt]');
    }

    private function connectDiscord(mixed $larp): void
    {
        $integration = LarpIntegrationFactory::new()->discord()->botInstall(IntegrationFixtureRouters::DISCORD_GUILD_ID)->forLarp($larp)->create([
            'config' => ['guildName' => 'Shadows of Ardenne LARP', DiscordBotPermissions::GRANTED_CONFIG_KEY => '309237713920'],
        ]);
        ExternalSourceFactory::new()->create([
            'integration' => $integration,
            'kind' => ExternalSourceKind::DiscordChannel,
            'externalId' => self::ORGANIZER_CHANNEL_ID,
            'title' => 'faction-planning',
            'config' => ['visibility' => ExternalSourceVisibility::OrganizerOnly->value],
        ]);
        ExternalSourceFactory::new()->create([
            'integration' => $integration,
            'kind' => ExternalSourceKind::DiscordForum,
            'externalId' => self::PLAYER_FORUM_ID,
            'title' => 'character-threads',
            'config' => ['visibility' => ExternalSourceVisibility::PlayerFacing->value],
        ]);
    }

    /**
     * @return list<array{request: string, json: array<string, mixed>}>
     */
    private function posts(FixtureRouter $router): array
    {
        $posts = [];
        foreach (array_slice($router->getRequests(), $this->requestsBefore) as $request) {
            if ($request['method'] !== 'POST' || !str_starts_with($request['path'], '/channels/')) {
                continue;
            }

            $posts[] = ['request' => 'POST ' . $request['path'], 'json' => json_decode($request['body'], true, flags: JSON_THROW_ON_ERROR)];
        }

        return $posts;
    }

    private function discordRouter(FunctionalTester $I): FixtureRouter
    {
        $I->assertInstanceOf(FixtureRouter::class, $this->discordRouter);

        return $this->discordRouter;
    }
}
