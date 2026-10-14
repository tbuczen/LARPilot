<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceSyncStatus;
use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\Integrations\ExternalItemFactory;
use Tests\Support\Factory\Integrations\ExternalSourceFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;
use Zenstruck\Foundry\Persistence\Proxy;

class IntegrationHubCest
{
    private const GOOGLE_CARD = '.integration-card[data-provider="integration_google_drive"]';

    private const LINK_FORM = 'form[name="integration_item_link"]';

    public function notConnectedProviderShowsConnectButton(FunctionalTester $I): void
    {
        $I->wantTo('see a Not connected Google card with a Connect button');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_hub', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Integrations', 'h1');
        $I->seeElement(self::GOOGLE_CARD . '[data-state="not_connected"]');
        $I->see('Not connected', self::GOOGLE_CARD . ' .badge');
        $I->seeElement(self::GOOGLE_CARD . ' a.btn-success[href="' . $this->connectUrl($I, $larp->getId()->toRfc4122()) . '"]');
        $I->dontSeeElement(self::GOOGLE_CARD . ' .btn-outline-danger');
        $I->see('Browse board items and link them to story objects.', '.integration-card[data-provider="integration_miro"]');
        $I->dontSee('integration_hub.purpose.');
    }

    public function connectedProviderShowsAccountSourcesAndLastSync(FunctionalTester $I): void
    {
        $I->wantTo('see a Connected card with account, source count and last sync time');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createGoogleIntegration($larp, $organizer, ['externalAccountId' => 'gm@example.com']);
        $this->createSource($integration, ['lastSyncedAt' => new \DateTimeImmutable('2026-03-04 05:06:07'), 'lastSyncStatus' => ExternalSourceSyncStatus::Success]);
        $this->createSource($integration);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_hub', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement(self::GOOGLE_CARD . '[data-state="connected"]');
        $I->see('Connected', self::GOOGLE_CARD . ' .badge');
        $I->see('gm@example.com', self::GOOGLE_CARD);
        $I->see('04-03-2026 05:06', self::GOOGLE_CARD);
        $I->see('2', self::GOOGLE_CARD . ' [data-source-count]');
        $I->seeElement(self::GOOGLE_CARD . ' a.btn-outline-primary[href="' . $I->getUrl('backoffice_larp_integrations_sources', [
            'larp' => $larp->getId()->toRfc4122(),
            'provider' => LarpIntegrationProvider::Google->value,
        ]) . '"]');
        $I->seeElement(self::GOOGLE_CARD . ' button.btn-outline-danger[data-bs-target="#deleteModal"]');
        $I->dontSeeElement(self::GOOGLE_CARD . ' a.btn-warning');
        $I->dontSee('googleapis.com');
    }

    public function revokedProviderNeedsReconnect(FunctionalTester $I): void
    {
        $I->wantTo('see a Needs reconnect card with a Reconnect button when access was revoked');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createGoogleIntegration($larp, $organizer, ['status' => IntegrationStatus::NeedsReauth, 'lastError' => 'invalid_grant']);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_hub', ['larp' => $larp->getId()]);

        $I->seeElement(self::GOOGLE_CARD . '[data-state="needs_reconnect"]');
        $I->see('Needs reconnect', self::GOOGLE_CARD . ' .badge');
        $I->seeElement(self::GOOGLE_CARD . ' a.btn-warning[href="' . $this->connectUrl($I, $larp->getId()->toRfc4122()) . '"]');
    }

    public function expiredTokenWithoutRefreshNeedsReconnect(FunctionalTester $I): void
    {
        $I->wantTo('see Needs reconnect when the access token expired and cannot be refreshed');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->google()->expired()->withoutRefreshToken()->create();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_hub', ['larp' => $larp->getId()]);

        $I->seeElement(self::GOOGLE_CARD . '[data-state="needs_reconnect"]');
    }

    public function erroredProviderShowsError(FunctionalTester $I): void
    {
        $I->wantTo('see an Error card when the integration is in error');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createGoogleIntegration($larp, $organizer, ['status' => IntegrationStatus::Error, 'lastError' => 'HTTP 500']);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_hub', ['larp' => $larp->getId()]);

        $I->seeElement(self::GOOGLE_CARD . '[data-state="error"]');
        $I->see('Error', self::GOOGLE_CARD . ' .badge');
        $I->seeElement(self::GOOGLE_CARD . ' .alert-danger');
    }

    public function failedSourceSyncShowsError(FunctionalTester $I): void
    {
        $I->wantTo('see an Error card when an enabled source failed to sync');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createGoogleIntegration($larp, $organizer);
        $this->createSource($integration, ['lastSyncStatus' => ExternalSourceSyncStatus::Failed, 'lastSyncError' => 'boom']);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_hub', ['larp' => $larp->getId()]);

        $I->seeElement(self::GOOGLE_CARD . '[data-state="error"]');
    }

    public function legacyProviderWithoutConnectCanBeDisconnected(FunctionalTester $I): void
    {
        $I->wantTo('see a stored Facebook integration without a Connect button but with Disconnect');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->facebook()->create();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_hub', ['larp' => $larp->getId()]);

        $facebookCard = '.integration-card[data-provider="facebook"]';
        $I->seeElement($facebookCard . '[data-state="connected"]');
        $I->seeElement($facebookCard . ' button.btn-outline-danger');
        $I->dontSeeElement($facebookCard . ' a.btn-success');
    }

    public function oldSettingsRouteRedirectsToHub(FunctionalTester $I): void
    {
        $I->wantTo('be redirected from the old integration settings URL to the hub');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->stopFollowingRedirects();
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIs(Response::HTTP_MOVED_PERMANENTLY);
        $I->assertSame(
            $I->getUrl('backoffice_larp_integrations_hub', ['larp' => $larp->getId()->toRfc4122()]),
            $I->grabResponse()->headers->get('Location'),
        );
    }

    public function storyWriterCannotOpenHubOrProviderPages(FunctionalTester $I): void
    {
        $I->wantTo('verify a story writer gets 403 on every hub page');

        $this->assertParticipantDenied($I, 'storyWriter');
    }

    public function staffCannotOpenHubOrProviderPages(FunctionalTester $I): void
    {
        $I->wantTo('verify a staff member gets 403 on every hub page');

        $this->assertParticipantDenied($I, 'staff');
    }

    public function storyWriterCannotDisconnect(FunctionalTester $I): void
    {
        $I->wantTo('verify a story writer cannot disconnect an integration');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createGoogleIntegration($larp, $organizer);
        $storyWriter = $this->createParticipant($larp, 'storyWriter');

        $I->amLoggedInAs($storyWriter);
        $I->sendAjaxPostRequest($this->disconnectUrl($I, $larp->getId()->toRfc4122()), ['_token' => 'irrelevant']);

        $I->seeResponseCodeIs(403);
        $I->seeInRepository(LarpIntegration::class, ['id' => $integration->getId()]);
    }

    public function disconnectRejectsGet(FunctionalTester $I): void
    {
        $I->wantTo('verify disconnect cannot be triggered with GET');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createGoogleIntegration($larp, $organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnPage($this->disconnectUrl($I, $larp->getId()->toRfc4122()));

        $I->seeResponseCodeIs(405);
        $I->seeInRepository(LarpIntegration::class, ['id' => $integration->getId()]);
    }

    public function disconnectRejectsMissingCsrf(FunctionalTester $I): void
    {
        $I->wantTo('verify disconnect requires a valid CSRF token');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createGoogleIntegration($larp, $organizer);

        $I->amLoggedInAs($organizer);
        $I->sendAjaxPostRequest($this->disconnectUrl($I, $larp->getId()->toRfc4122()), ['_token' => 'forged']);

        $I->seeResponseCodeIs(403);
        $I->seeInRepository(LarpIntegration::class, ['id' => $integration->getId()]);
    }

    public function organizerDisconnectsThroughConfirmModal(FunctionalTester $I): void
    {
        $I->wantTo('disconnect Google Drive through the confirmation modal');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createGoogleIntegration($larp, $organizer);
        $integrationId = $integration->getId();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_hub', ['larp' => $larp->getId()]);
        $I->seeElement('#deleteModal button.btn-danger');
        $token = $I->grabValueFrom('#deleteModal input[name="_token"]');

        $I->stopFollowingRedirects();
        $I->sendAjaxPostRequest($this->disconnectUrl($I, $larp->getId()->toRfc4122()), ['_token' => $token]);

        $I->seeResponseCodeIsRedirection();
        $I->dontSeeInRepository(LarpIntegration::class, ['id' => $integrationId]);
    }

    public function providerDetailShowsTabsAndSources(FunctionalTester $I): void
    {
        $I->wantTo('see the Google provider page with its tabs and sources');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createGoogleIntegration($larp, $organizer);
        $this->createSource($integration, ['title' => 'Character sheet']);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_sources', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Google->value]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Google Drive', 'h1');
        $I->see('Sources', '.nav-tabs .active');
        $I->see('Browse', '.nav-tabs');
        $I->see('Activity', '.nav-tabs');
        $I->see('Mappings', '.nav-tabs');
        $I->see('Character sheet');
        $I->dontSee('Sync now');
    }

    public function notConnectedProviderDetailRedirectsToHub(FunctionalTester $I): void
    {
        $I->wantTo('be sent back to the hub when opening a provider that is not connected');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->stopFollowingRedirects();
        $I->amOnRoute('backoffice_larp_integrations_browse', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Google->value]);

        $I->seeResponseCodeIsRedirection();
        $I->assertSame(
            $I->getUrl('backoffice_larp_integrations_hub', ['larp' => $larp->getId()->toRfc4122()]),
            $I->grabResponse()->headers->get('Location'),
        );
    }

    public function mappingsTabIsGoogleOnly(FunctionalTester $I): void
    {
        $I->wantTo('verify only Google has a Mappings tab');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->discord()->botInstall('123456')->create();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_sources', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Discord->value]);
        $I->seeResponseCodeIsSuccessful();
        $I->dontSee('Mappings', '.nav-tabs');

        $I->amOnRoute('backoffice_larp_integrations_mappings', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Discord->value]);
        $I->seeResponseCodeIs(404);
    }

    public function sourceCanBePausedWithCsrf(FunctionalTester $I): void
    {
        $I->wantTo('pause a source from the Sources tab');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createGoogleIntegration($larp, $organizer);
        $source = $this->createSource($integration, ['title' => 'Lore folder']);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_sources', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Google->value]);
        $I->submitForm('form[name="integration_source_toggle"]', []);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Paused');
        $I->seeInRepository(ExternalSource::class, ['id' => $source->getId(), 'enabled' => false]);
    }

    public function sourceToggleRejectsMissingCsrf(FunctionalTester $I): void
    {
        $I->wantTo('verify toggling a source requires a CSRF token');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createGoogleIntegration($larp, $organizer);
        $source = $this->createSource($integration);

        $I->amLoggedInAs($organizer);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integrations_source_toggle', [
            'larp' => $larp->getId()->toRfc4122(),
            'provider' => LarpIntegrationProvider::Google->value,
            'source' => $source->getId()->toRfc4122(),
        ]), ['enabled' => '0']);

        $I->seeResponseCodeIs(403);
        $I->seeInRepository(ExternalSource::class, ['id' => $source->getId(), 'enabled' => true]);
    }

    public function syncNowIsHiddenForProvidersThatCannotSync(FunctionalTester $I): void
    {
        $I->wantTo('verify Sync now is not offered for a provider without sync support and still needs CSRF');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createGoogleIntegration($larp, $organizer);
        $source = $this->createSource($integration);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_sources', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Google->value]);
        $I->dontSeeElement('form[name="integration_source_sync"]');

        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integrations_source_sync', [
            'larp' => $larp->getId()->toRfc4122(),
            'provider' => LarpIntegrationProvider::Google->value,
            'source' => $source->getId()->toRfc4122(),
        ]), ['_token' => 'forged']);
        $I->seeResponseCodeIs(403);
    }

    public function activityTabExplainsFailuresInPlainLanguage(FunctionalTester $I): void
    {
        $I->wantTo('read sync failures in plain language on the Activity tab');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createGoogleIntegration($larp, $organizer);
        $this->createSource($integration, [
            'title' => 'Plot board',
            'lastSyncedAt' => new \DateTimeImmutable('2026-05-06 07:08:09'),
            'lastSyncStatus' => ExternalSourceSyncStatus::Failed,
            'lastSyncError' => 'HTTP 503 upstream',
        ]);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_activity', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Google->value]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Plot board');
        $I->see('The sync failed. LARPilot will try again later.');
        $I->see('06-05-2026 07:08');
        $I->see('HTTP 503 upstream', 'details');
    }

    public function browseTabSearchesItemsOfThisProvider(FunctionalTester $I): void
    {
        $I->wantTo('search synced items of one provider on the Browse tab');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $discord = LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->discord()->botInstall('123456')->create();
        $miro = LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->miro()->create();
        $discordSource = $this->createSource($discord, ['kind' => ExternalSourceKind::DiscordChannel]);
        $miroSource = $this->createSource($miro, ['kind' => ExternalSourceKind::MiroBoard]);
        ExternalItemFactory::new()->create(['source' => $discordSource, 'title' => 'Dragon heist thread']);
        ExternalItemFactory::new()->create(['source' => $discordSource, 'title' => 'Tavern rumours']);
        ExternalItemFactory::new()->create(['source' => $miroSource, 'title' => 'Dragon board sticky']);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_browse', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Discord->value,
            'q' => 'dragon',
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Dragon heist thread');
        $I->dontSee('Tavern rumours');
        $I->dontSee('Dragon board sticky');
        $I->seeElement(self::LINK_FORM);
    }

    public function browseLinksItemToStoryObject(FunctionalTester $I): void
    {
        $I->wantTo('link a synced item to a character from the Browse tab');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $discord = LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->discord()->botInstall('123456')->create();
        $source = $this->createSource($discord, ['kind' => ExternalSourceKind::DiscordChannel]);
        $item = ExternalItemFactory::new()->create(['source' => $source, 'title' => 'Dragon heist thread']);
        $character = CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->create(['title' => 'Old Smaug']);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_story_object_search', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Discord->value,
            'q' => 'smaug',
        ]);
        $I->seeResponseCodeIsSuccessful();
        $I->see('Old Smaug');

        $I->amOnRoute('backoffice_larp_integrations_browse', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Discord->value]);
        $I->submitForm(self::LINK_FORM, [
            'item' => $item->getId()->toRfc4122(),
            'storyObject' => $character->getId()->toRfc4122(),
            'role' => ReferenceRole::Primary->value,
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Linked to Old Smaug.');
        $I->seeInRepository(ExternalReference::class, [
            'storyObject' => $character->getId(),
            'externalItem' => $item->getId(),
            'role' => ReferenceRole::Primary,
        ]);
    }

    public function browseLinkRejectsItemOfAnotherProvider(FunctionalTester $I): void
    {
        $I->wantTo('verify the Browse link cannot attach an item of another provider');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $discord = LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->discord()->botInstall('123456')->create();
        $miro = LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->miro()->create();
        ExternalItemFactory::new()->create(['source' => $this->createSource($discord, ['kind' => ExternalSourceKind::DiscordChannel])]);
        $miroItem = ExternalItemFactory::new()->create(['source' => $this->createSource($miro, ['kind' => ExternalSourceKind::MiroBoard])]);
        $character = CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->create();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_browse', ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Discord->value]);
        $token = $I->grabValueFrom(self::LINK_FORM . ' input[name="_token"]');
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integrations_item_link', [
            'larp' => $larp->getId()->toRfc4122(),
            'provider' => LarpIntegrationProvider::Discord->value,
        ]), [
            '_token' => $token,
            'item' => $miroItem->getId()->toRfc4122(),
            'storyObject' => $character->getId()->toRfc4122(),
        ]);

        $I->seeResponseCodeIs(404);
        $I->dontSeeInRepository(ExternalReference::class, ['storyObject' => $character->getId()]);
    }

    public function dashboardShowsIntegrationsNeedingAttention(FunctionalTester $I): void
    {
        $I->wantTo('see how many integrations need attention on the LARP dashboard');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createGoogleIntegration($larp, $organizer, ['status' => IntegrationStatus::NeedsReauth]);
        LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->discord()->botInstall('123456')->create();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_dashboard', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Integrations needing attention', '[data-dashboard-tile="integrations"]');
        $I->see('1', '[data-integrations-needing-attention]');
        $I->see('2 connected', '[data-dashboard-tile="integrations"]');
    }

    public function hubShowsOneMenuEntry(FunctionalTester $I): void
    {
        $I->wantTo('find exactly one Integrations entry in the LARP menu');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integrations_hub', ['larp' => $larp->getId()]);

        $I->seeNumberOfElements('header a[href="' . $I->getUrl('backoffice_larp_integrations_hub', ['larp' => $larp->getId()->toRfc4122()]) . '"]', 1);
    }

    private function assertParticipantDenied(FunctionalTester $I, string $roleState): void
    {
        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createGoogleIntegration($larp, $organizer);
        $participant = $this->createParticipant($larp, $roleState);
        $providerParameters = ['larp' => $larp->getId(), 'provider' => LarpIntegrationProvider::Google->value];

        $I->amLoggedInAs($participant);
        foreach (['hub', 'sources', 'browse', 'activity', 'mappings', 'story_object_search'] as $page) {
            $I->amOnRoute('backoffice_larp_integrations_' . $page, $providerParameters);
            $I->seeResponseCodeIs(403);
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createGoogleIntegration(Larp|Proxy $larp, User|Proxy $organizer, array $attributes = []): LarpIntegration|Proxy
    {
        return LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->google()->create($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createSource(LarpIntegration|Proxy $integration, array $attributes = []): ExternalSource|Proxy
    {
        return ExternalSourceFactory::new()->create(array_merge(['integration' => $integration], $attributes));
    }

    private function createParticipant(Larp|Proxy $larp, string $roleState): User
    {
        $user = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($user)->{$roleState}()->create();

        return $user;
    }

    private function connectUrl(FunctionalTester $I, string $larpId): string
    {
        return $I->getUrl('backoffice_larp_connect_integration', [
            'larp' => $larpId,
            'provider' => LarpIntegrationProvider::Google->value,
        ]);
    }

    private function disconnectUrl(FunctionalTester $I, string $larpId): string
    {
        return $I->getUrl('backoffice_larp_integrations_disconnect', [
            'larp' => $larpId,
            'provider' => LarpIntegrationProvider::Google->value,
        ]);
    }
}
