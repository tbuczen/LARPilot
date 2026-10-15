<?php

declare(strict_types=1);

namespace Tests\Functional\StoryObject\Character;

use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use Doctrine\Common\Collections\ArrayCollection;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;

class CharacterIntegrationSyncCest
{
    public function editingCharacterWithGoogleIntegrationShowsNoWarning(FunctionalTester $I): void
    {
        $I->wantTo('edit a character in a LARP with a Google integration without a sync warning');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $character = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Original Name')
            ->create();

        $integration = new LarpIntegration();
        $integration->setProvider(LarpIntegrationProvider::Google);
        $integration->setAccessToken('test-access-token');
        $integration->setRefreshToken('test-refresh-token');
        $integration->setExpiresAt(new \DateTimeImmutable('+1 day'));
        $integration->setScopes('https://www.googleapis.com/auth/drive.file');
        $integration->setLarp($larp->_real());
        $integration->setCreatedBy($organizer);

        $sharedFiles = new \ReflectionProperty(LarpIntegration::class, 'sharedFiles');
        $sharedFiles->setValue($integration, new ArrayCollection());

        $entityManager = $I->getEntityManager();
        $entityManager->persist($integration);
        $entityManager->flush();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_story_character_modify', [
            'larp' => $larp->getId(),
            'character' => $character->getId(),
        ]);
        $I->seeResponseCodeIsSuccessful();

        $I->submitForm('form[name="character"]', [
            'character[title]' => 'Renamed Character',
        ]);

        $I->seeCurrentRouteIs('backoffice_larp_story_character_list', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('.alert-success');
        $I->dontSeeElement('.alert-warning');
        $I->dontSee('Failed to sync with');
    }
}
