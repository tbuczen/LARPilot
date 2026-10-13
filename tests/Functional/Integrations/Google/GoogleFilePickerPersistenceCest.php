<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations\Google;

use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\Integrations\Entity\SharedFile;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\Integrations\SharedFileFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Integrations\FixtureGoogleClientFactory;

class GoogleFilePickerPersistenceCest
{
    private const NEW_FILE_ID = 'newly-picked-spreadsheet';

    public function _before(): void
    {
        FixtureGoogleClientFactory::reset();
    }

    public function savingThePickerPersistsRemovalsPermissionChangesAndAdditions(FunctionalTester $I): void
    {
        $I->wantTo('save the file picker after removing a file, changing a permission and picking a new file');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->google()->create()->_real();
        $keptFile = SharedFileFactory::new()->forIntegration($integration)->spreadsheet()->create()->_real();
        $removedFile = SharedFileFactory::new()->forIntegration($integration)->spreadsheet()->readOnly()->create()->_real();
        $removedFileMappingId = $this->createMapping($I, $larp->_real(), $removedFile)->getId();
        FixtureGoogleClientFactory::grantServiceAccountRole($keptFile->getFileId(), 'writer');
        FixtureGoogleClientFactory::grantServiceAccountRole($removedFile->getFileId(), 'reader');

        $I->amLoggedInAs($organizer);
        $this->submitPicker($I, $larp->_real(), $integration, [
            ['fileId' => $keptFile->getFileId(), 'fileName' => $keptFile->getFileName(), 'permission' => 'reader', 'mimeType' => $keptFile->getMimeType()],
            ['fileId' => self::NEW_FILE_ID, 'fileName' => 'Casting', 'permission' => 'writer', 'mimeType' => SharedFileFactory::SPREADSHEET_MIME_TYPE],
        ]);

        $I->seeCurrentRouteIs('backoffice_larp_integration_settings', ['larp' => $larp->getId()->toRfc4122()]);

        $sharedFiles = $this->sharedFilesByFileId($I, $integration);
        $I->assertEqualsCanonicalizing([$keptFile->getFileId(), self::NEW_FILE_ID], array_keys($sharedFiles));
        $I->assertSame('reader', $sharedFiles[$keptFile->getFileId()]->getPermissionType());
        $I->assertSame('writer', $sharedFiles[self::NEW_FILE_ID]->getPermissionType());
        $I->assertSame('https://docs.google.com/document/d/' . self::NEW_FILE_ID . '/edit', $sharedFiles[self::NEW_FILE_ID]->getUrl());
        $I->assertNull($I->getEntityManager()->find(ObjectFieldMapping::class, $removedFileMappingId));

        $I->assertSame('reader', FixtureGoogleClientFactory::serviceAccountRole($keptFile->getFileId()));
        $I->assertSame('writer', FixtureGoogleClientFactory::serviceAccountRole(self::NEW_FILE_ID));
        $I->assertCount(1, FixtureGoogleClientFactory::requestsMatching('PATCH', '#/permissions/' . FixtureGoogleClientFactory::SERVICE_ACCOUNT_PERMISSION_ID . '$#'));
    }

    public function savingAnEmptyPickerRemovesEveryFile(FunctionalTester $I): void
    {
        $I->wantTo('remove the last picked file and keep it removed after saving');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->google()->create()->_real();
        SharedFileFactory::new()->forIntegration($integration)->document()->create();

        $I->amLoggedInAs($organizer);
        $this->submitPicker($I, $larp->_real(), $integration, []);

        $I->assertSame([], $this->sharedFilesByFileId($I, $integration));
    }

    public function malformedSelectionIsRejectedWithoutTouchingFiles(FunctionalTester $I): void
    {
        $I->wantTo('verify a malformed picker selection is rejected instead of removing every file');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = LarpIntegrationFactory::new()->forLarp($larp)->createdBy($organizer)->google()->create()->_real();
        $sharedFile = SharedFileFactory::new()->forIntegration($integration)->document()->create()->_real();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);
        $I->submitForm($this->pickerFormSelector($I, $larp->_real(), $integration), ['selectedFiles' => '']);

        $I->seeResponseCodeIs(400);
        $I->assertSame([$sharedFile->getFileId()], array_keys($this->sharedFilesByFileId($I, $integration)));
    }

    /**
     * @param list<array{fileId: string, fileName: string, permission: string, mimeType: string}> $selectedFiles
     */
    private function submitPicker(FunctionalTester $I, Larp $larp, LarpIntegration $integration, array $selectedFiles): void
    {
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);
        $I->submitForm($this->pickerFormSelector($I, $larp, $integration), [
            'selectedFiles' => json_encode($selectedFiles, JSON_THROW_ON_ERROR),
        ]);
        $I->seeResponseCodeIsSuccessful();
    }

    private function pickerFormSelector(FunctionalTester $I, Larp $larp, LarpIntegration $integration): string
    {
        return sprintf('form[action="%s"]', $I->getUrl('backoffice_larp_integration_file_permissions', [
            'larp' => $larp->getId(),
            'integrationId' => $integration->getId(),
        ]));
    }

    /**
     * @return array<string, SharedFile>
     */
    private function sharedFilesByFileId(FunctionalTester $I, LarpIntegration $integration): array
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();

        $sharedFiles = [];
        foreach ($entityManager->getRepository(SharedFile::class)->findBy(['integration' => $integration->getId()]) as $sharedFile) {
            $sharedFiles[$sharedFile->getFileId()] = $sharedFile;
        }

        return $sharedFiles;
    }

    private function createMapping(FunctionalTester $I, Larp $larp, SharedFile $sharedFile): ObjectFieldMapping
    {
        $mapping = new ObjectFieldMapping();
        $mapping->setCreatedBy($sharedFile->getIntegration()->getCreatedBy());
        $mapping->setLarp($larp);
        $mapping->setExternalFile($sharedFile);
        $mapping->setFileType(ResourceType::TAG_LIST);
        $mapping->setMappingConfiguration(['title' => 'A']);
        $mapping->setMetaConfiguration(['startingRow' => 2, 'sheetName' => 'Characters', 'endColumn' => 'D']);
        $sharedFile->getMappings()->add($mapping);

        $entityManager = $I->getEntityManager();
        $entityManager->persist($mapping);
        $entityManager->flush();

        return $mapping;
    }
}
