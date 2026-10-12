<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations;

use App\Domain\Account\Entity\User;
use App\Domain\Integrations\Entity\Enum\ExternalItemAvailability;
use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Entity\Enum\ExternalItemReviewStatus;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;
use App\Domain\Integrations\Service\ExternalReferenceLinker;
use App\Domain\StoryObject\Entity\Character;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\Integrations\ExternalItemFactory;
use Tests\Support\Factory\Integrations\ExternalReferenceFactory;
use Tests\Support\Factory\Integrations\ExternalSourceFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;
use Zenstruck\Foundry\Persistence\Proxy;

class StorySourcesCest
{
    private const LINK_FORM = 'form[name="story_source_link"]';
    private const DISCORD_MESSAGE_URL = 'https://discord.com/channels/111111111111111111/222222222222222222/333333333333333333';

    public function organizerSeesEmptyState(FunctionalTester $I): void
    {
        $I->wantTo('see the empty state and the link button on a story object without sources');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_story_sources_list', $this->routeParameters($character));

        $I->seeResponseCodeIsSuccessful();
        $I->see('No external sources are linked yet.');
        $I->seeElement('button.btn-success[data-bs-target="#linkSourceModal"]');
        $I->seeElement(self::LINK_FORM);
    }

    public function storyObjectTabsShowSourcesCount(FunctionalTester $I): void
    {
        $I->wantTo('see the Sources tab with its count on the character page');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);
        ExternalReferenceFactory::new()->forStoryObject($character)->createdBy($organizer)->create();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_story_character_modify', [
            'larp' => $character->getLarp()->getId(),
            'character' => $character->getId(),
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Sources', 'a.nav-link');
        $I->see('1', 'a.nav-link[href$="/sources"] .badge');
        $I->dontSeeElement('#referencesCollapse');
    }

    public function linkedSourcesAreGroupedByProvider(FunctionalTester $I): void
    {
        $I->wantTo('see linked sources grouped by provider with their row details');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);
        $item = $this->createCachedItem($character, LarpIntegrationProvider::Discord, 'cached-thread');
        $item->_real()->setAvailability(ExternalItemAvailability::Archived);
        $item->_save();
        $this->linkItem($I, $character, $item->_real(), $organizer);
        ExternalReferenceFactory::new()
            ->forStoryObject($character)
            ->createdBy($organizer)
            ->withProvider(LarpIntegrationProvider::Miro)
            ->withReferenceType(ReferenceType::BoardItem)
            ->withRole(ReferenceRole::Primary)
            ->create(['name' => 'Throne room frame', 'url' => 'https://miro.com/app/board/abc=/?moveToWidget=1']);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_story_sources_list', $this->routeParameters($character));

        $I->seeResponseCodeIsSuccessful();
        $I->seeNumberOfElements('.story-sources__group', 2);
        $I->see('Throne room frame', '.story-sources__group[data-provider="miro"] a[target="_blank"][rel="noopener"]');
        $I->see('Board item', '.story-sources__group[data-provider="miro"] .badge');
        $I->see('Primary', '.story-sources__group[data-provider="miro"] .badge');
        $I->see((string) $item->getTitle(), '.story-sources__group[data-provider="discord"] a');
        $I->see('Archived', '.story-sources__group[data-provider="discord"] .badge');
        $I->seeElement('.story-sources__row a.btn-sm.btn-outline-info');
        $I->seeElement('.story-sources__row button.btn-sm.btn-outline-danger[data-bs-target="#deleteModal"]');
    }

    public function writerCanLinkCachedItem(FunctionalTester $I): void
    {
        $I->wantTo('verify a story writer can link a cached item');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);
        $writer = $this->createParticipant($character, 'storyWriter');
        $item = $this->createCachedItem($character, LarpIntegrationProvider::Discord, '444');

        $I->amLoggedInAs($writer);
        $I->amOnRoute('backoffice_larp_story_sources_list', $this->routeParameters($character));
        $token = $I->grabValueFrom(self::LINK_FORM . ' input[name="_token"]');
        $I->stopFollowingRedirects();

        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_story_sources_link', $this->routeParameters($character)), [
            '_token' => $token,
            'item' => $item->getId()->toRfc4122(),
            'role' => 'primary',
        ]);

        $I->seeResponseCodeIsRedirection();
        $reference = $this->findReference($I, $character, LarpIntegrationProvider::Discord, '444');
        $I->assertNotNull($reference);
        $I->assertSame($item->getId()->toRfc4122(), $reference->getExternalItem()?->getId()->toRfc4122());
        $I->assertSame(ReferenceRole::Primary, $reference->getRole());
        $I->assertSame($writer->getId()->toRfc4122(), $reference->getCreatedBy()?->getId()->toRfc4122());
        $I->assertSame(ExternalItemReviewStatus::Linked, $item->_refresh()->getReviewStatus());
    }

    public function pastedUrlWithoutCachedItemStoresReference(FunctionalTester $I): void
    {
        $I->wantTo('link a pasted Discord URL that has no cached item');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_story_sources_list', $this->routeParameters($character));
        $I->submitForm(self::LINK_FORM, ['url' => self::DISCORD_MESSAGE_URL]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Source linked.');
        $reference = $this->findReference($I, $character, LarpIntegrationProvider::Discord, '333333333333333333');
        $I->assertNotNull($reference);
        $I->assertNull($reference->getExternalItem());
        $I->assertSame(ReferenceType::Comment, $reference->getReferenceType());
        $I->assertSame(ReferenceRole::Mention, $reference->getRole());
        $I->assertSame(self::DISCORD_MESSAGE_URL, $reference->getUrl());
    }

    public function pastedUrlReusesCachedItem(FunctionalTester $I): void
    {
        $I->wantTo('link a pasted Miro URL to the matching cached item');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);
        $item = $this->createCachedItem($character, LarpIntegrationProvider::Miro, '3458764512345678901', ExternalItemKind::Frame);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_story_sources_list', $this->routeParameters($character));
        $I->submitForm(self::LINK_FORM, ['url' => 'https://miro.com/app/board/uXjVKabc=/?moveToWidget=3458764512345678901']);

        $I->seeResponseCodeIsSuccessful();
        $reference = $this->findReference($I, $character, LarpIntegrationProvider::Miro, '3458764512345678901');
        $I->assertNotNull($reference);
        $I->assertSame($item->getId()->toRfc4122(), $reference->getExternalItem()?->getId()->toRfc4122());
        $I->assertSame(ReferenceType::BoardFrame, $reference->getReferenceType());
    }

    public function unsupportedUrlIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('see an error when pasting an unsupported URL');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_story_sources_list', $this->routeParameters($character));
        $I->submitForm(self::LINK_FORM, ['url' => 'https://example.com/not-a-source']);

        $I->seeResponseCodeIsSuccessful();
        $I->see('This link is not a recognised Discord or Miro address.');
        $I->dontSeeInRepository(ExternalReference::class, ['storyObject' => $character->_real()]);
    }

    public function itemFromAnotherLarpCannotBeLinked(FunctionalTester $I): void
    {
        $I->wantTo('verify a cached item of another LARP cannot be linked');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);
        $foreignCharacter = $this->createCharacter(UserFactory::createApprovedUser());
        $foreignItem = $this->createCachedItem($foreignCharacter, LarpIntegrationProvider::Discord, '555');

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_story_sources_list', $this->routeParameters($character));
        $token = $I->grabValueFrom(self::LINK_FORM . ' input[name="_token"]');
        $I->stopFollowingRedirects();
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_story_sources_link', $this->routeParameters($character)), [
            '_token' => $token,
            'item' => $foreignItem->getId()->toRfc4122(),
        ]);

        $I->seeResponseCodeIsRedirection();
        $I->dontSeeInRepository(ExternalReference::class, ['storyObject' => $character->_real()]);
    }

    public function searchReturnsOnlyItemsOfThisLarp(FunctionalTester $I): void
    {
        $I->wantTo('search cached items of the current LARP only');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);
        $item = $this->createCachedItem($character, LarpIntegrationProvider::Discord, '666');
        $item->_real()->setTitle('Dragon council minutes');
        $item->_save();
        $foreignItem = $this->createCachedItem($this->createCharacter(UserFactory::createApprovedUser()), LarpIntegrationProvider::Discord, '777');
        $foreignItem->_real()->setTitle('Dragon council leak');
        $foreignItem->_save();

        $I->amLoggedInAs($organizer);
        $I->sendAjaxGetRequest($I->getUrl('backoffice_larp_story_sources_search', $this->routeParameters($character)), ['q' => 'dragon']);

        $I->seeResponseCodeIsSuccessful();
        $payload = json_decode($I->grabPageSource(), true);
        $I->assertCount(1, $payload['items']);
        $I->assertSame($item->getId()->toRfc4122(), $payload['items'][0]['id']);
        $I->assertSame('Dragon council minutes', $payload['items'][0]['title']);
    }

    public function writerCanUnlink(FunctionalTester $I): void
    {
        $I->wantTo('verify a story writer can unlink a source');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);
        $writer = $this->createParticipant($character, 'mainStoryWriter');
        $reference = ExternalReferenceFactory::new()->forStoryObject($character)->createdBy($organizer)->create();
        $referenceId = $reference->getId();

        $I->amLoggedInAs($writer);
        $I->amOnRoute('backoffice_larp_story_sources_list', $this->routeParameters($character));
        $token = $I->grabValueFrom('#deleteModal input[name="_token"]');
        $I->stopFollowingRedirects();
        $I->sendAjaxPostRequest($this->unlinkUrl($I, $character, $referenceId->toRfc4122()), ['_token' => $token]);

        $I->seeResponseCodeIsRedirection();
        $I->dontSeeInRepository(ExternalReference::class, ['id' => $referenceId]);
    }

    public function linkRequiresCsrfToken(FunctionalTester $I): void
    {
        $I->wantTo('verify linking without a valid CSRF token is rejected');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);

        $I->amLoggedInAs($organizer);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_story_sources_link', $this->routeParameters($character)), [
            '_token' => 'invalid',
            'url' => self::DISCORD_MESSAGE_URL,
        ]);

        $I->seeResponseCodeIs(403);
        $I->dontSeeInRepository(ExternalReference::class, ['storyObject' => $character->_real()]);
    }

    public function unlinkRequiresCsrfToken(FunctionalTester $I): void
    {
        $I->wantTo('verify unlinking without a valid CSRF token is rejected');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);
        $reference = ExternalReferenceFactory::new()->forStoryObject($character)->createdBy($organizer)->create();
        $referenceId = $reference->getId();

        $I->amLoggedInAs($organizer);
        $I->sendAjaxPostRequest($this->unlinkUrl($I, $character, $referenceId->toRfc4122()), ['_token' => 'invalid']);

        $I->seeResponseCodeIs(403);
        $I->seeInRepository(ExternalReference::class, ['id' => $referenceId]);
    }

    public function unlinkRejectsGet(FunctionalTester $I): void
    {
        $I->wantTo('verify unlinking is POST only');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);
        $reference = ExternalReferenceFactory::new()->forStoryObject($character)->createdBy($organizer)->create();
        $referenceId = $reference->getId();

        $I->amLoggedInAs($organizer);
        $I->sendAjaxGetRequest($this->unlinkUrl($I, $character, $referenceId->toRfc4122()));

        $I->seeResponseCodeIs(405);
        $I->seeInRepository(ExternalReference::class, ['id' => $referenceId]);
    }

    public function referenceOfAnotherStoryObjectCannotBeUnlinked(FunctionalTester $I): void
    {
        $I->wantTo('verify a reference cannot be unlinked through another story object');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);
        $otherCharacter = CharacterFactory::new()->forLarp($character->getLarp())->withCreator($organizer)->create();
        $reference = ExternalReferenceFactory::new()->forStoryObject($otherCharacter)->createdBy($organizer)->create();
        $referenceId = $reference->getId();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_story_sources_list', $this->routeParameters($character));
        $token = $I->grabValueFrom('#deleteModal input[name="_token"]');
        $I->stopFollowingRedirects();
        $I->sendAjaxPostRequest($this->unlinkUrl($I, $character, $referenceId->toRfc4122()), ['_token' => $token]);

        $I->seeResponseCodeIs(404);
        $I->seeInRepository(ExternalReference::class, ['id' => $referenceId]);
    }

    public function playerCannotViewSources(FunctionalTester $I): void
    {
        $I->wantTo('verify a player cannot open the Sources tab');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);
        $player = $this->createParticipant($character, 'player');

        $I->amLoggedInAs($player);
        $I->amOnRoute('backoffice_larp_story_sources_list', $this->routeParameters($character));

        $I->seeResponseCodeIs(403);
    }

    public function playerCannotLink(FunctionalTester $I): void
    {
        $I->wantTo('verify a player cannot link a source');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);
        $player = $this->createParticipant($character, 'player');

        $I->amLoggedInAs($player);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_story_sources_link', $this->routeParameters($character)), [
            'url' => self::DISCORD_MESSAGE_URL,
        ]);

        $I->seeResponseCodeIs(403);
        $I->dontSeeInRepository(ExternalReference::class, ['storyObject' => $character->_real()]);
    }

    public function staffCanViewButCannotLinkOrUnlink(FunctionalTester $I): void
    {
        $I->wantTo('verify staff can view sources but cannot link or unlink');

        $organizer = UserFactory::createApprovedUser();
        $character = $this->createCharacter($organizer);
        $staffMember = $this->createParticipant($character, 'staff');
        $reference = ExternalReferenceFactory::new()->forStoryObject($character)->createdBy($organizer)->create();
        $referenceId = $reference->getId();

        $I->amLoggedInAs($staffMember);
        $I->amOnRoute('backoffice_larp_story_sources_list', $this->routeParameters($character));
        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeElement('#linkSourceModal');
        $I->dontSeeElement('.story-sources__row button.btn-outline-danger');

        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_story_sources_link', $this->routeParameters($character)), [
            'url' => self::DISCORD_MESSAGE_URL,
        ]);
        $I->seeResponseCodeIs(403);

        $I->sendAjaxPostRequest($this->unlinkUrl($I, $character, $referenceId->toRfc4122()));
        $I->seeResponseCodeIs(403);

        $I->sendAjaxGetRequest($I->getUrl('backoffice_larp_story_sources_search', $this->routeParameters($character)));
        $I->seeResponseCodeIs(403);

        $I->seeInRepository(ExternalReference::class, ['id' => $referenceId]);
        $I->dontSeeInRepository(ExternalReference::class, ['storyObject' => $character->_real(), 'provider' => LarpIntegrationProvider::Discord]);
    }

    private function createCharacter(User|Proxy $organizer): Character|Proxy
    {
        $larp = LarpFactory::createDraftLarp($organizer);

        return CharacterFactory::new()->forLarp($larp)->withCreator($organizer)->create();
    }

    private function createParticipant(Character|Proxy $character, string $roleState): User
    {
        $user = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()
            ->forLarp($character->getLarp())
            ->forUser($user)
            ->{$roleState}()
            ->create();

        return $user instanceof Proxy ? $user->_real() : $user;
    }

    private function createCachedItem(
        Character|Proxy $character,
        LarpIntegrationProvider $provider,
        string $externalId,
        ExternalItemKind $kind = ExternalItemKind::Thread,
    ): ExternalItem|Proxy {
        $integration = LarpIntegrationFactory::new()
            ->forLarp($character->getLarp())
            ->withProvider($provider)
            ->create();
        $source = ExternalSourceFactory::new()->create([
            'integration' => $integration,
            'kind' => $provider === LarpIntegrationProvider::Miro ? ExternalSourceKind::MiroBoard : ExternalSourceKind::DiscordChannel,
        ]);

        return ExternalItemFactory::new()->create([
            'source' => $source,
            'externalId' => $externalId,
            'kind' => $kind,
        ]);
    }

    private function linkItem(FunctionalTester $I, Character|Proxy $character, ExternalItem $item, User|Proxy $user): void
    {
        $linker = $I->grabService(ExternalReferenceLinker::class);
        $linker->linkItem($character->_real(), $item, ReferenceRole::Mention, $user instanceof Proxy ? $user->_real() : $user);
    }

    private function findReference(FunctionalTester $I, Character|Proxy $character, LarpIntegrationProvider $provider, string $externalId): ?ExternalReference
    {
        $I->getEntityManager()->clear();

        return $I->grabService(ExternalReferenceRepository::class)->findOneBy([
            'storyObject' => $character->getId(),
            'provider' => $provider,
            'externalId' => $externalId,
        ]);
    }

    private function routeParameters(Character|Proxy $character): array
    {
        return [
            'larp' => $character->getLarp()->getId(),
            'storyObject' => $character->getId(),
        ];
    }

    private function unlinkUrl(FunctionalTester $I, Character|Proxy $character, string $referenceId): string
    {
        return $I->getUrl('backoffice_larp_story_sources_unlink', $this->routeParameters($character) + ['reference' => $referenceId]);
    }
}
