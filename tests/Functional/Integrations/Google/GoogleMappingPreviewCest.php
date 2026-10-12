<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Google;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\Integrations\Entity\SharedFile;
use Symfony\Component\Routing\RouterInterface;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\Integrations\SharedFileFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Integrations\FixtureGoogleClientFactory;

class GoogleMappingPreviewCest
{
    public function _before(): void
    {
        FixtureGoogleClientFactory::reset();
    }

    public function mappingFormPreviewsTheFirstRowsAndOffersHeaderDropdowns(FunctionalTester $I): void
    {
        $I->wantTo('see the first rows of the mapped sheet and choose columns by header name');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $sharedFile = $this->createSpreadsheet($larp->_real());
        $mapping = $this->createCharacterListMapping($I, $larp->_real(), $organizer, $sharedFile);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_file_mapping', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google->value,
            'sharedFile' => $sharedFile->getId(),
            'mapping' => $mapping->getId(),
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Name', '[data-spreadsheet-preview] thead');
        $I->see('Player', '[data-spreadsheet-preview] thead');
        $I->see('Lady Morwen', '[data-spreadsheet-preview] tbody');
        $I->see('Cousin Elric', '[data-spreadsheet-preview] tbody');
        $I->seeNumberOfElements('[data-spreadsheet-preview] tbody tr', 3);

        $I->seeElement('select[name="file_mapping[meta][sheetName]"] option[value="Characters"][selected]');
        $I->seeElement('select[name="file_mapping[meta][sheetName]"] option[value="Cover"]');
        $I->see('Name (A)', 'select[name="file_mapping[mappings][title]"] option[value="A"]');
        $I->see('Faction (B)', 'select[name="file_mapping[mappings][factions]"] option[value="B"]');
        $I->seeElement('select[name="file_mapping[mappings][title]"] option[value="A"][selected]');
        $I->dontSeeElement('input[name="file_mapping[mappings][title]"]');
    }

    public function previewStartsAtTheHeaderRowOfTheChosenSheet(FunctionalTester $I): void
    {
        $I->wantTo('read the preview from the header row right above the starting row');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $sharedFile = $this->createSpreadsheet($larp->_real());
        $mapping = $this->createCharacterListMapping($I, $larp->_real(), $organizer, $sharedFile);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_file_mapping', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google->value,
            'sharedFile' => $sharedFile->getId(),
            'mapping' => $mapping->getId(),
        ]);

        $valueRequests = FixtureGoogleClientFactory::requestsMatching('GET', '#^/v4/spreadsheets/[^/]+/values/#');
        $I->assertCount(1, $valueRequests);
        $I->assertStringEndsWith("/values/'Characters'!1:6", $valueRequests[0]['path']);
    }

    public function savingTheMappingStoresTheChosenColumnLetters(FunctionalTester $I): void
    {
        $I->wantTo('save a mapping picked from header dropdowns');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $sharedFile = $this->createSpreadsheet($larp->_real());
        $mapping = $this->createCharacterListMapping($I, $larp->_real(), $organizer, $sharedFile);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_file_mapping', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google->value,
            'sharedFile' => $sharedFile->getId(),
            'mapping' => $mapping->getId(),
        ]);
        $I->submitForm('form[name="file_mapping"]', [
            'file_mapping[mappingType]' => ResourceType::CHARACTER_LIST->value,
            'file_mapping[meta][sheetName]' => 'Characters',
            'file_mapping[meta][endColumn]' => 'D',
            'file_mapping[meta][startingRow]' => '2',
            'file_mapping[mappings][title]' => 'A',
            'file_mapping[mappings][inGameName]' => 'C',
            'file_mapping[mappings][factions]' => 'B',
            'file_mapping[mappings][description]' => 'D',
        ]);

        $I->seeCurrentRouteIs('backoffice_larp_external_resource_mapping', [
            'larp' => $larp->getId()->toRfc4122(),
            'integration' => $sharedFile->getIntegration()->getId()->toRfc4122(),
            'sharedFile' => $sharedFile->getId()->toRfc4122(),
        ]);

        $entityManager = $I->getEntityManager();
        $entityManager->clear();
        $savedMapping = $entityManager->getRepository(ObjectFieldMapping::class)->findOneBy(['externalFile' => $sharedFile->getId()]);
        $I->assertInstanceOf(ObjectFieldMapping::class, $savedMapping);
        $I->assertSame('A', $savedMapping->getMappingConfiguration()['title']);
        $I->assertSame('C', $savedMapping->getMappingConfiguration()['inGameName']);
        $I->assertSame('D', $savedMapping->getMappingConfiguration()['description']);
        $I->assertSame('Characters', $savedMapping->getMetaConfiguration()['sheetName']);
    }

    public function placeholderPreviewEndpointIsGone(FunctionalTester $I): void
    {
        $I->wantTo('verify the TODO spreadsheet preview endpoint no longer exists');

        /** @var RouterInterface $router */
        $router = $I->grabService('router');

        $I->assertNull($router->getRouteCollection()->get('backoffice_larp_integration_preview_spreadsheet'));
    }

    private function createSpreadsheet(Larp $larp): SharedFile
    {
        $integration = LarpIntegrationFactory::new()->forLarp($larp)->google()->create();

        return SharedFileFactory::new()->forIntegration($integration)->spreadsheet()->create()->_real();
    }

    private function createCharacterListMapping(FunctionalTester $I, Larp $larp, User $organizer, SharedFile $sharedFile): ObjectFieldMapping
    {
        $mapping = new ObjectFieldMapping();
        $mapping->setCreatedBy($organizer);
        $mapping->setLarp($larp);
        $mapping->setExternalFile($sharedFile);
        $mapping->setFileType(ResourceType::CHARACTER_LIST);
        $mapping->setMappingConfiguration(['title' => 'A', 'factions' => 'B']);
        $mapping->setMetaConfiguration(['startingRow' => 2, 'sheetName' => 'Characters', 'endColumn' => 'D']);
        $sharedFile->getMappings()->add($mapping);

        $entityManager = $I->getEntityManager();
        $entityManager->persist($mapping);
        $entityManager->flush();

        return $mapping;
    }
}
