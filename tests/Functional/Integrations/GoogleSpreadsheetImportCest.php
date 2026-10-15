<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Service\Capability\FileSourceIntegrationInterface;
use App\Domain\Integrations\Service\IntegrationRegistry;
use App\Domain\StoryObject\Entity\Character;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\Integrations\SharedFileFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Integrations\FixtureGoogleClientFactory;

class GoogleSpreadsheetImportCest
{
    private const SHEET_CHARACTER_TITLES = ['Brother Aldric', 'Cousin Elric', 'Lady Morwen'];

    public function characterImportReadsTheMappedSheetThroughTheFileSourceCapability(FunctionalTester $I): void
    {
        $I->wantTo('run the character import endpoint against a mapped Google Sheet');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        foreach (self::SHEET_CHARACTER_TITLES as $characterTitle) {
            CharacterFactory::new()->forLarp($larp)->with(['title' => $characterTitle])->create();
        }
        $sharedFile = $this->createMappedSpreadsheet($I, $larp->_real(), $organizer, ResourceType::CHARACTER_LIST, [
            'title' => 'A',
            'factions' => 'B',
        ]);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_story_character_import_file_select', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google->value,
        ]);
        $I->submitForm($this->importFormSelector($I, 'backoffice_larp_story_character_import_from_mapping', $larp->_real(), $sharedFile), []);

        $I->seeResponseCodeIsSuccessful();
        $I->seeCurrentRouteIs('backoffice_larp_story_character_list', ['larp' => $larp->getId()->toRfc4122()]);
        $I->assertSame(self::SHEET_CHARACTER_TITLES, $this->characterTitles($I, $larp->_real()));
    }

    public function tagImportReadsTheMappedSheetThroughTheFileSourceCapability(FunctionalTester $I): void
    {
        $I->wantTo('run the tag import endpoint against a mapped Google Sheet');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createDescribedTag($I, $larp->_real(), $organizer, 'House Morwen');
        $this->createDescribedTag($I, $larp->_real(), $organizer, "Smugglers' Guild");
        $sharedFile = $this->createMappedSpreadsheet($I, $larp->_real(), $organizer, ResourceType::TAG_LIST, [
            'title' => 'B',
        ]);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_story_tag_import_file_select', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google->value,
        ]);
        $I->submitForm($this->importFormSelector($I, 'backoffice_larp_story_tag_import_from_mapping', $larp->_real(), $sharedFile), []);

        $I->seeResponseCodeIsSuccessful();
        $I->seeCurrentRouteIs('backoffice_larp_story_tag_list', ['larp' => $larp->getId()->toRfc4122()]);
        $I->seeElement('.alert-warning');
        $I->seeElement('.alert-success');
    }

    public function fileSourceReadsRowsAndBuildsRowReferenceUrls(FunctionalTester $I): void
    {
        $I->wantTo('read a mapped Google Sheet and build row links through the file source capability');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $sharedFile = $this->createMappedSpreadsheet($I, $larp->_real(), $organizer, ResourceType::CHARACTER_LIST, [
            'title' => 'A',
            'factions' => 'B',
        ]);
        $mapping = $sharedFile->getMappings()->first();

        /** @var IntegrationRegistry $integrationRegistry */
        $integrationRegistry = $I->grabService(IntegrationRegistry::class);
        $fileSource = $integrationRegistry->capability($sharedFile->getIntegration(), FileSourceIntegrationInterface::class);

        $rows = $fileSource->fetchSpreadsheetRows($sharedFile, $mapping);
        $sheetId = $fileSource->fetchSpreadsheetSheetIdByName($sharedFile, $mapping);

        $I->assertCount(4, $rows);
        $I->assertSame(['A' => 'Lady Morwen', 'B' => 'House Morwen', 'C' => 'Anna', 'D' => 'Wants the seal back'], $rows[1]);
        $I->assertSame(['A' => 'Cousin Elric', 'B' => 'House Morwen', 'C' => 'Piotr', 'D' => null], $rows[3]);
        $I->assertSame(FixtureGoogleClientFactory::CHARACTERS_SHEET_ID, $sheetId);
        $I->assertSame(
            sprintf('%s#gid=%s&range=2:2', $this->urlWithoutQuery($sharedFile), FixtureGoogleClientFactory::CHARACTERS_SHEET_ID),
            $fileSource->createReferenceUrl($sharedFile, ReferenceType::SpreadsheetRow, 2, ['sheetId' => $sheetId])
        );
    }

    /**
     * @param array<string, string> $columnMapping
     */
    private function createMappedSpreadsheet(FunctionalTester $I, Larp $larp, User $organizer, ResourceType $resourceType, array $columnMapping): SharedFile
    {
        $integration = LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->google()->create();
        $sharedFile = SharedFileFactory::new()->forIntegration($integration)->spreadsheet()->create()->_real();

        $mapping = new ObjectFieldMapping();
        $mapping->setCreatedBy($organizer);
        $mapping->setLarp($larp);
        $mapping->setExternalFile($sharedFile);
        $mapping->setFileType($resourceType);
        $mapping->setMappingConfiguration($columnMapping);
        $mapping->setMetaConfiguration([
            'startingRow' => 2,
            'sheetName' => 'Characters',
            'endColumn' => 'D',
        ]);
        $sharedFile->getMappings()->add($mapping);

        $entityManager = $I->getEntityManager();
        $entityManager->persist($mapping);
        $entityManager->flush();

        return $sharedFile;
    }

    private function createDescribedTag(FunctionalTester $I, Larp $larp, User $organizer, string $tagTitle): void
    {
        $tag = new Tag();
        $tag->setLarp($larp);
        $tag->setTitle($tagTitle);
        $tag->setDescription('Already described in LARPilot');
        $tag->setCreatedBy($organizer);

        $entityManager = $I->getEntityManager();
        $entityManager->persist($tag);
        $entityManager->flush();
    }

    /**
     * @return list<string>
     */
    private function characterTitles(FunctionalTester $I, Larp $larp): array
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();

        return array_map(
            static fn (Character $character): string => (string) $character->getTitle(),
            $entityManager->getRepository(Character::class)->findBy(['larp' => $larp->getId()], ['title' => 'ASC'])
        );
    }

    private function importFormSelector(FunctionalTester $I, string $route, Larp $larp, SharedFile $sharedFile): string
    {
        $mapping = $sharedFile->getMappings()->first();

        return sprintf('form[action="%s"]', $I->getUrl($route, [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google->value,
            'sharedFile' => $sharedFile->getId(),
            'mapping' => $mapping->getId(),
        ]));
    }

    private function urlWithoutQuery(SharedFile $sharedFile): string
    {
        return (string) preg_replace('/\?.*/', '', (string) $sharedFile->getUrl());
    }
}
