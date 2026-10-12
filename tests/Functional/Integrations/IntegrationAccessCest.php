<?php

declare(strict_types=1);

namespace Tests\Functional\Integrations;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\Integrations\Entity\SharedFile;
use Doctrine\Common\Collections\ArrayCollection;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\FunctionalTester;

class IntegrationAccessCest
{
    private const ACCESS_TOKEN = 'secret-access-token-that-must-not-leak';
    private const REFRESH_TOKEN = 'secret-refresh-token-that-must-not-leak';

    public function organizerCanOpenIntegrationSettings(FunctionalTester $I): void
    {
        $I->wantTo('verify an organizer can open integration settings');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createIntegration($I, $larp->_real(), $organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('[data-controller="google-file-picker"]');
    }

    public function settingsHtmlNeverContainsCredentials(FunctionalTester $I): void
    {
        $I->wantTo('verify the settings page never renders integration credentials');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createIntegration($I, $larp->_real(), $organizer, self::REFRESH_TOKEN);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeInSource(self::ACCESS_TOKEN);
        $I->dontSeeInSource(self::REFRESH_TOKEN);
        $I->dontSeeElement('[data-oauth-token]');
    }

    public function storyWriterCannotOpenIntegrationSettings(FunctionalTester $I): void
    {
        $I->wantTo('verify a story writer cannot open integration settings');

        $this->assertParticipantDeniedSettings($I, ['storyWriter']);
    }

    public function npcCannotOpenIntegrationSettings(FunctionalTester $I): void
    {
        $I->wantTo('verify an NPC cannot open integration settings');

        $this->assertParticipantDeniedSettings($I, ['npcLong']);
    }

    public function medicCannotOpenIntegrationSettings(FunctionalTester $I): void
    {
        $I->wantTo('verify a medic cannot open integration settings');

        $this->assertParticipantDeniedSettings($I, ['medic']);
    }

    public function staffCannotRequestPickerToken(FunctionalTester $I): void
    {
        $I->wantTo('verify a staff member cannot request a picker token');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createIntegration($I, $larp->_real(), $organizer);
        $staffMember = $this->createParticipant($larp, 'staff');

        $I->amLoggedInAs($staffMember);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integration_picker_token', [
            'larp' => $larp->getId(),
            'integration' => $integration->getId(),
        ]));

        $I->seeResponseCodeIs(403);
        $I->assertStringNotContainsString(self::ACCESS_TOKEN, (string) $I->grabResponse()->getContent());
    }

    public function pickerTokenRejectsGet(FunctionalTester $I): void
    {
        $I->wantTo('verify the picker token endpoint rejects GET');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createIntegration($I, $larp->_real(), $organizer);

        $I->amLoggedInAs($organizer);
        $I->sendAjaxGetRequest($I->getUrl('backoffice_larp_integration_picker_token', [
            'larp' => $larp->getId(),
            'integration' => $integration->getId(),
        ]));

        $I->seeResponseCodeIs(405);
    }

    public function pickerTokenRejectsMissingCsrf(FunctionalTester $I): void
    {
        $I->wantTo('verify the picker token endpoint rejects a request without a CSRF token');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createIntegration($I, $larp->_real(), $organizer);

        $I->amLoggedInAs($organizer);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integration_picker_token', [
            'larp' => $larp->getId(),
            'integration' => $integration->getId(),
        ]));

        $I->seeResponseCodeIs(403);
        $I->assertStringNotContainsString(self::ACCESS_TOKEN, (string) $I->grabResponse()->getContent());
    }

    public function pickerTokenWithCsrfIsNotCached(FunctionalTester $I): void
    {
        $I->wantTo('verify the picker token endpoint accepts a valid CSRF token and forbids caching');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createIntegration($I, $larp->_real(), $organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);
        $csrfToken = $I->grabAttributeFrom('[data-controller="google-file-picker"]', 'data-google-file-picker-token-csrf-value');

        $I->haveHttpHeader('X-CSRF-Token', $csrfToken);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integration_picker_token', [
            'larp' => $larp->getId(),
            'integration' => $integration->getId(),
        ]));

        $I->seeResponseCodeIsSuccessful();
        $I->assertSame(['accessToken' => self::ACCESS_TOKEN], json_decode((string) $I->grabResponse()->getContent(), true));
        $I->assertStringContainsString('no-store', (string) $I->grabResponse()->headers->get('Cache-Control'));
    }

    public function foreignIntegrationIsNotFoundInResourceMapping(FunctionalTester $I): void
    {
        $I->wantTo('verify an integration of another LARP cannot be opened through my LARP');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $foreignIntegration = $this->createForeignIntegration($I);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_external_resource_mapping', [
            'larp' => $larp->getId(),
            'integration' => $foreignIntegration->getId(),
        ]);

        $I->seeResponseCodeIs(404);
    }

    public function organizerCanOpenOwnResourceMapping(FunctionalTester $I): void
    {
        $I->wantTo('verify an organizer can open the resource mapping of their LARP');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createIntegration($I, $larp->_real(), $organizer);
        $sharedFile = $this->createSharedFile($I, $integration);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_external_resource_mapping', [
            'larp' => $larp->getId(),
            'integration' => $integration->getId(),
            'sharedFile' => $sharedFile->getId(),
        ]);

        $I->seeResponseCodeIsSuccessful();
    }

    public function foreignSharedFileIsNotFoundInFileMapping(FunctionalTester $I): void
    {
        $I->wantTo('verify a shared file of another LARP cannot be mapped through my LARP');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $foreignSharedFile = $this->createSharedFile($I, $this->createForeignIntegration($I));

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_integration_file_mapping', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google->value,
            'sharedFile' => $foreignSharedFile->getId(),
        ]);

        $I->seeResponseCodeIs(404);
    }

    public function storyWriterCannotOpenFileMapping(FunctionalTester $I): void
    {
        $I->wantTo('verify a story writer cannot open file mapping');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $sharedFile = $this->createSharedFile($I, $this->createIntegration($I, $larp->_real(), $organizer));
        $storyWriter = $this->createParticipant($larp, 'storyWriter');

        $I->amLoggedInAs($storyWriter);
        $I->amOnRoute('backoffice_larp_integration_file_mapping', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google->value,
            'sharedFile' => $sharedFile->getId(),
        ]);

        $I->seeResponseCodeIs(403);
    }

    public function filePermissionsRejectForeignIntegration(FunctionalTester $I): void
    {
        $I->wantTo('verify file permissions cannot be applied to another LARP integration');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $foreignIntegration = $this->createForeignIntegration($I);

        $I->amLoggedInAs($organizer);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integration_file_permissions', [
            'larp' => $larp->getId(),
            'integrationId' => $foreignIntegration->getId(),
        ]), ['selectedFiles' => '[]']);

        $I->seeResponseCodeIs(404);
    }

    public function filePermissionsRejectMissingCsrf(FunctionalTester $I): void
    {
        $I->wantTo('verify file permissions reject a request without a CSRF token');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createIntegration($I, $larp->_real(), $organizer);

        $I->amLoggedInAs($organizer);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integration_file_permissions', [
            'larp' => $larp->getId(),
            'integrationId' => $integration->getId(),
        ]), ['selectedFiles' => '[]']);

        $I->seeResponseCodeIs(403);
    }

    public function filePermissionsRejectGet(FunctionalTester $I): void
    {
        $I->wantTo('verify file permissions reject GET');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createIntegration($I, $larp->_real(), $organizer);

        $I->amLoggedInAs($organizer);
        $I->sendAjaxGetRequest($I->getUrl('backoffice_larp_integration_file_permissions', [
            'larp' => $larp->getId(),
            'integrationId' => $integration->getId(),
        ]));

        $I->seeResponseCodeIs(405);
    }

    public function staffCannotApplyFilePermissions(FunctionalTester $I): void
    {
        $I->wantTo('verify a staff member cannot apply file permissions');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $integration = $this->createIntegration($I, $larp->_real(), $organizer);
        $staffMember = $this->createParticipant($larp, 'staff');

        $I->amLoggedInAs($staffMember);
        $I->sendAjaxPostRequest($I->getUrl('backoffice_larp_integration_file_permissions', [
            'larp' => $larp->getId(),
            'integrationId' => $integration->getId(),
        ]), ['selectedFiles' => '[]']);

        $I->seeResponseCodeIs(403);
    }

    public function importFromMappingRejectsGet(FunctionalTester $I): void
    {
        $I->wantTo('verify importing from a mapping rejects GET');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $sharedFile = $this->createSharedFile($I, $this->createIntegration($I, $larp->_real(), $organizer));
        $mapping = $this->createMapping($I, $larp->_real(), $sharedFile, ResourceType::CHARACTER_LIST);

        $I->amLoggedInAs($organizer);
        $I->sendAjaxGetRequest($this->importUrl($I, 'backoffice_larp_story_character_import_from_mapping', $larp->_real(), $sharedFile, $mapping));

        $I->seeResponseCodeIs(405);
    }

    public function importFromMappingRejectsMissingCsrf(FunctionalTester $I): void
    {
        $I->wantTo('verify importing from a mapping rejects a request without a CSRF token');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $sharedFile = $this->createSharedFile($I, $this->createIntegration($I, $larp->_real(), $organizer));
        $mapping = $this->createMapping($I, $larp->_real(), $sharedFile, ResourceType::TAG_LIST);

        $I->amLoggedInAs($organizer);
        $I->sendAjaxPostRequest($this->importUrl($I, 'backoffice_larp_story_tag_import_from_mapping', $larp->_real(), $sharedFile, $mapping));

        $I->seeResponseCodeIs(403);
    }

    public function importFromForeignMappingIsNotFound(FunctionalTester $I): void
    {
        $I->wantTo('verify a mapping of another LARP cannot be imported through my LARP');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $foreignSharedFile = $this->createSharedFile($I, $this->createForeignIntegration($I));
        $foreignMapping = $this->createMapping($I, $foreignSharedFile->getIntegration()->getLarp(), $foreignSharedFile, ResourceType::CHARACTER_LIST);

        $I->amLoggedInAs($organizer);
        $I->sendAjaxPostRequest($this->importUrl($I, 'backoffice_larp_story_character_import_from_mapping', $larp->_real(), $foreignSharedFile, $foreignMapping));

        $I->seeResponseCodeIs(404);
    }

    public function storyWriterCannotImportFromMapping(FunctionalTester $I): void
    {
        $I->wantTo('verify a story writer cannot import from a mapping');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $sharedFile = $this->createSharedFile($I, $this->createIntegration($I, $larp->_real(), $organizer));
        $mapping = $this->createMapping($I, $larp->_real(), $sharedFile, ResourceType::CHARACTER_LIST);
        $storyWriter = $this->createParticipant($larp, 'storyWriter');

        $I->amLoggedInAs($storyWriter);
        $I->sendAjaxPostRequest($this->importUrl($I, 'backoffice_larp_story_character_import_from_mapping', $larp->_real(), $sharedFile, $mapping));

        $I->seeResponseCodeIs(403);
    }

    public function importButtonsAreCsrfProtectedForms(FunctionalTester $I): void
    {
        $I->wantTo('verify the import buttons submit a POST form with a CSRF token');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $sharedFile = $this->createSharedFile($I, $this->createIntegration($I, $larp->_real(), $organizer));
        $mapping = $this->createMapping($I, $larp->_real(), $sharedFile, ResourceType::CHARACTER_LIST);
        $importUrl = $this->importUrl($I, 'backoffice_larp_story_character_import_from_mapping', $larp->_real(), $sharedFile, $mapping);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_story_character_import_file_select', [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google->value,
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement(sprintf('form[method="post"][action="%s"] input[name="_token"]', $importUrl));
        $I->dontSeeElement(sprintf('a[href="%s"]', $importUrl));
    }

    /**
     * @param list<string> $roleStates
     */
    private function assertParticipantDeniedSettings(FunctionalTester $I, array $roleStates): void
    {
        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $this->createIntegration($I, $larp->_real(), $organizer);
        $participant = $this->createParticipant($larp, ...$roleStates);

        $I->amLoggedInAs($participant);
        $I->amOnRoute('backoffice_larp_integration_settings', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIs(403);
        $I->dontSeeInSource(self::ACCESS_TOKEN);
    }

    private function createParticipant(mixed $larp, string ...$roleStates): User
    {
        $user = UserFactory::createApprovedUser();
        $participantFactory = LarpParticipantFactory::new()
            ->forLarp($larp)
            ->forUser($user);

        foreach ($roleStates as $roleState) {
            $participantFactory = $participantFactory->{$roleState}();
        }

        $participantFactory->create();

        return $user;
    }

    private function createForeignIntegration(FunctionalTester $I): LarpIntegration
    {
        $foreignOrganizer = UserFactory::createApprovedUser();
        $foreignLarp = LarpFactory::createDraftLarp($foreignOrganizer);

        return $this->createIntegration($I, $foreignLarp->_real(), $foreignOrganizer);
    }

    private function createIntegration(FunctionalTester $I, Larp $larp, User $creator, ?string $refreshToken = null): LarpIntegration
    {
        $integration = new LarpIntegration();
        $integration->setProvider(LarpIntegrationProvider::Google);
        $integration->setAccessToken(self::ACCESS_TOKEN);
        $integration->setRefreshToken($refreshToken);
        $integration->setExpiresAt(new \DateTimeImmutable('+1 hour'));
        $integration->setScopes('https://www.googleapis.com/auth/drive.file');
        $integration->setLarp($larp);
        $integration->setCreatedBy($creator);

        $sharedFiles = new \ReflectionProperty(LarpIntegration::class, 'sharedFiles');
        $sharedFiles->setValue($integration, new ArrayCollection());

        $entityManager = $I->getEntityManager();
        $entityManager->persist($integration);
        $entityManager->flush();

        return $integration;
    }

    private function createSharedFile(FunctionalTester $I, LarpIntegration $integration): SharedFile
    {
        $sharedFile = new SharedFile();
        $sharedFile->setIntegration($integration);
        $sharedFile->setFileId('drive-file-' . bin2hex(random_bytes(4)));
        $sharedFile->setFileName('Characters');
        $sharedFile->setMimeType('application/vnd.google-apps.spreadsheet');
        $sharedFile->setPermissionType('reader');
        $sharedFile->setMetadata([]);
        $integration->addSharedFile($sharedFile);

        $entityManager = $I->getEntityManager();
        $entityManager->persist($sharedFile);
        $entityManager->flush();

        return $sharedFile;
    }

    private function createMapping(FunctionalTester $I, Larp $larp, SharedFile $sharedFile, ResourceType $resourceType): ObjectFieldMapping
    {
        $mapping = new ObjectFieldMapping();
        $mapping->setCreatedBy($sharedFile->getIntegration()->getCreatedBy());
        $mapping->setLarp($larp);
        $mapping->setExternalFile($sharedFile);
        $mapping->setFileType($resourceType);
        $mapping->setMappingConfiguration([]);
        $mapping->setMetaConfiguration([]);
        $sharedFile->getMappings()->add($mapping);

        $entityManager = $I->getEntityManager();
        $entityManager->persist($mapping);
        $entityManager->flush();

        return $mapping;
    }

    private function importUrl(FunctionalTester $I, string $route, Larp $larp, SharedFile $sharedFile, ObjectFieldMapping $mapping): string
    {
        return $I->getUrl($route, [
            'larp' => $larp->getId(),
            'provider' => LarpIntegrationProvider::Google->value,
            'sharedFile' => $sharedFile->getId(),
            'mapping' => $mapping->getId(),
        ]);
    }
}
