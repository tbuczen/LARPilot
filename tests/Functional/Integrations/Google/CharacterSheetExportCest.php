<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Google;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Service\CharacterSheetExportService;
use App\Domain\StoryObject\Entity\Character;
use Google\Service\Drive;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\Integrations\SharedFileFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Integrations\FixtureGoogleClientFactory;

class CharacterSheetExportCest
{
    private User $organizer;

    public function _before(): void
    {
        FixtureGoogleClientFactory::reset();
    }

    public function firstExportCopiesTheTemplateIntoTheCharacterFolder(FunctionalTester $I): void
    {
        $I->wantTo('export a character sheet for the first time');

        $character = $this->createExportableCharacter($I);

        $export = $this->exportService($I)->exportCharacterSheet($character);

        $I->assertFalse($export['existed']);
        $I->assertSame('created-file-1', $export['documentId']);
        $I->assertSame('https://docs.google.com/document/d/created-file-1/edit', $export['documentUrl']);
        $I->assertCount(1, FixtureGoogleClientFactory::requestsMatching('POST', '#^/drive/v3/files/[^/]+/copy$#'));
        $I->assertCount(0, FixtureGoogleClientFactory::requestsMatching('DELETE', '#^/drive/v3/files/#'));
        $I->assertSame('created-file-1', $this->characterSheetReference($I, $character)->getExternalId());
    }

    public function secondExportUpdatesTheSameDocument(FunctionalTester $I): void
    {
        $I->wantTo('export a character sheet twice and keep the same Google Doc');

        $character = $this->createExportableCharacter($I);
        $exportService = $this->exportService($I);

        $firstExport = $exportService->exportCharacterSheet($character);
        $secondExport = $exportService->exportCharacterSheet($character);

        $I->assertTrue($secondExport['existed']);
        $I->assertSame($firstExport['documentId'], $secondExport['documentId']);
        $I->assertSame($firstExport['documentUrl'], $secondExport['documentUrl']);

        $documentId = $firstExport['documentId'];
        $contentUploads = FixtureGoogleClientFactory::requestsMatching('PATCH', '#^/upload/drive/v3/files/' . preg_quote($documentId, '#') . '$#');
        $I->assertCount(1, $contentUploads);
        $I->assertSame('multipart', $contentUploads[0]['query']['uploadType']);

        $deletedFileIds = array_map(
            static fn (array $request): string => basename($request['path']),
            FixtureGoogleClientFactory::requestsMatching('DELETE', '#^/drive/v3/files/#')
        );
        $I->assertNotContains($documentId, $deletedFileIds);
        $I->assertSame(['created-file-2'], $deletedFileIds);

        $I->assertSame($documentId, $this->characterSheetReference($I, $character)->getExternalId());
        $I->assertCount(1, $I->getEntityManager()->getRepository(ExternalReference::class)->findBy([
            'storyObject' => $character,
            'referenceType' => ReferenceType::Document,
        ]));
    }

    public function exportRecreatesTheDocumentWhenTheReferencedOneWasDeleted(FunctionalTester $I): void
    {
        $I->wantTo('get a fresh character sheet when the previously exported doc was deleted in Drive');

        $character = $this->createExportableCharacter($I);
        $exportService = $this->exportService($I);

        $firstExport = $exportService->exportCharacterSheet($character);
        $this->trashInDrive($I, $character, $firstExport['documentId']);
        $secondExport = $exportService->exportCharacterSheet($character);

        $I->assertFalse($secondExport['existed']);
        $I->assertNotSame($firstExport['documentId'], $secondExport['documentId']);
        $I->assertSame($secondExport['documentId'], $this->characterSheetReference($I, $character)->getExternalId());
    }

    public function exportEndpointRedirectsBackWithTheDocumentLink(FunctionalTester $I): void
    {
        $I->wantTo('export a character sheet from the character page twice');

        $character = $this->createExportableCharacter($I);
        $larp = $character->getLarp();

        $I->amLoggedInAs($this->organizer);
        $I->stopFollowingRedirects();
        foreach ([1, 2] as $exportNumber) {
            $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_character_export_sheet', [
                'larp' => $larp->getId(),
                'character' => $character->getId(),
            ]));
            $I->seeResponseCodeIsRedirection();
        }

        $I->assertCount(1, FixtureGoogleClientFactory::requestsMatching('PATCH', '#^/upload/drive/v3/files/created-file-1$#'));
    }

    private function createExportableCharacter(FunctionalTester $I): Character
    {
        $organizer = UserFactory::createApprovedUser();
        $this->organizer = $organizer;
        $larp = LarpFactory::createDraftLarp($organizer)->_real();
        $integration = LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->google()->create()->_real();

        $template = SharedFileFactory::new()->forIntegration($integration)->document()->create()->_real();
        $directory = SharedFileFactory::new()->forIntegration($integration)->folder()->create()->_real();
        $this->createMapping($I, $larp, $organizer, $template, ResourceType::CHARACTER_DOC_TEMPLATE, []);
        $this->createMapping($I, $larp, $organizer, $directory, ResourceType::CHARACTER_DOC_DIRECTORY, ['groupByFaction' => false]);

        return CharacterFactory::new()->forLarp($larp)->with(['title' => 'Lady Morwen'])->create()->_real();
    }

    /**
     * @param array<string, mixed> $mappingConfiguration
     */
    private function createMapping(FunctionalTester $I, Larp $larp, User $organizer, SharedFile $sharedFile, ResourceType $resourceType, array $mappingConfiguration): void
    {
        $mapping = new ObjectFieldMapping();
        $mapping->setCreatedBy($organizer);
        $mapping->setLarp($larp);
        $mapping->setExternalFile($sharedFile);
        $mapping->setFileType($resourceType);
        $mapping->setMappingConfiguration($mappingConfiguration);
        $mapping->setMetaConfiguration([]);
        $sharedFile->getMappings()->add($mapping);

        $entityManager = $I->getEntityManager();
        $entityManager->persist($mapping);
        $entityManager->flush();
    }

    private function exportService(FunctionalTester $I): CharacterSheetExportService
    {
        return $I->grabService(CharacterSheetExportService::class);
    }

    private function characterSheetReference(FunctionalTester $I, Character $character): ExternalReference
    {
        $reference = $I->getEntityManager()->getRepository(ExternalReference::class)->findOneBy([
            'storyObject' => $character,
            'referenceType' => ReferenceType::Document,
            'role' => ReferenceRole::Primary,
        ]);
        $I->assertInstanceOf(ExternalReference::class, $reference);

        return $reference;
    }

    private function trashInDrive(FunctionalTester $I, Character $character, string $documentId): void
    {
        $template = $I->getEntityManager()->getRepository(ObjectFieldMapping::class)->findOneBy([
            'larp' => $character->getLarp(),
            'fileType' => ResourceType::CHARACTER_DOC_TEMPLATE,
        ]);
        $integration = $template?->getExternalFile()?->getIntegration();
        $I->assertInstanceOf(LarpIntegration::class, $integration);

        $drive = new Drive((new FixtureGoogleClientFactory())->getClientForIntegration($integration));
        $drive->files->delete($documentId);
    }
}
