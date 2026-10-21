<?php

declare(strict_types=1);

namespace Tests\Functional\CallTranscript;

use App\Domain\CallTranscript\Entity\CallTranscriptSettings;
use App\Domain\Integrations\Discord\DiscordBotPermissions;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceVisibility;
use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\ExternalSourceFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\Integrations\SharedFileFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Http\IntegrationFixtureRouters;

class CallTranscriptSettingsCest
{
    private const CHANNEL_ID = '1100000000000000210';

    public function defaultsArePreselectedOnTheUploadForm(FunctionalTester $I): void
    {
        $I->wantTo('save per-LARP defaults and see them preselected for the next upload');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $google = LarpIntegrationFactory::new()->google()->forLarp($larp)->createdBy($organizer)->create();
        $folder = SharedFileFactory::new()->forIntegration($google)->folder()->create(['fileName' => 'Transkrypcje']);
        $discord = LarpIntegrationFactory::new()->discord()->botInstall(IntegrationFixtureRouters::DISCORD_GUILD_ID)->forLarp($larp)->create([
            'config' => ['guildName' => 'Shadows of Ardenne LARP', DiscordBotPermissions::GRANTED_CONFIG_KEY => '309237713920'],
        ]);
        ExternalSourceFactory::new()->create([
            'integration' => $discord,
            'kind' => ExternalSourceKind::DiscordChannel,
            'externalId' => self::CHANNEL_ID,
            'title' => 'faction-planning',
            'config' => ['visibility' => ExternalSourceVisibility::OrganizerOnly->value],
        ]);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
        $I->seeCheckboxIsChecked('#call_recording_upload_summary');
        $I->seeOptionIsSelected('call_recording_upload[googleDocFolder]', "Don't create a Google Doc");

        $I->uncheckOption('call_transcript_settings[summary]');
        $I->selectOption('call_transcript_settings[googleDocFolder]', $folder->getId()->toRfc4122());
        $I->selectOption('call_transcript_settings[discordChannel]', self::CHANNEL_ID);
        $I->click('form[name="call_transcript_settings"] button[type="submit"]');

        $I->see('Call transcript defaults saved.');
        $I->dontSeeCheckboxIsChecked('#call_recording_upload_summary');
        $I->seeOptionIsSelected('call_recording_upload[googleDocFolder]', 'Transkrypcje');
        $I->seeOptionIsSelected('call_recording_upload[discordChannel]', '#faction-planning');

        $settings = $I->grabEntityFromRepository(CallTranscriptSettings::class, ['larp' => $larp->_real()]);
        $I->assertFalse($settings->isSummaryEnabled());
        $I->assertSame(self::CHANNEL_ID, $settings->getDiscordChannelId());
        $mapping = $I->grabEntityFromRepository(ObjectFieldMapping::class, ['larp' => $larp->_real(), 'fileType' => ResourceType::CALL_TRANSCRIPT_DIRECTORY]);
        $I->assertSame($folder->getId()->toRfc4122(), $mapping->getExternalFile()?->getId()->toRfc4122());
    }

    public function pageExplainsHowToConnectMissingIntegrations(FunctionalTester $I): void
    {
        $I->wantTo('see where to connect Google Drive and Discord when they are missing');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);

        $I->see('Connect Google Drive to also save each transcript as a Google Doc.');
        $I->see('Connect Discord to post each summary in a Discord thread.');
        $I->dontSeeElement('[name="call_transcript_settings[googleDocFolder]"]');
        $I->dontSeeElement('[name="call_transcript_settings[discordChannel]"]');
    }
}
