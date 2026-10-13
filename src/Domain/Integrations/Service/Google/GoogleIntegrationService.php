<?php

namespace App\Domain\Integrations\Service\Google;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\DTO\ConnectionCallback;
use App\Domain\Integrations\DTO\IntegrationHealth;
use App\Domain\Integrations\DTO\SpreadsheetPreview;
use App\Domain\Integrations\Entity\Enum\ConnectionType;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Form\Models\SpreadsheetMappingModel;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Service\Capability\AccessTokenProviderInterface;
use App\Domain\Integrations\Service\Capability\ConnectableIntegrationInterface;
use App\Domain\Integrations\Service\Capability\FileSourceIntegrationInterface;
use App\Domain\Integrations\Service\Credential\OAuthCredentialProvider;
use App\Domain\Integrations\Service\Exceptions\DuplicateStoryObjectException;
use App\Domain\Integrations\Service\Exceptions\IntegrationConnectionFailedException;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use App\Domain\StoryObject\Controller\Backoffice\CharacterController;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\StoryObject;
use Exception;
use Google\Service\Drive;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;
use RuntimeException;
use Webmozart\Assert\Assert;

readonly class GoogleIntegrationService implements
    ConnectableIntegrationInterface,
    FileSourceIntegrationInterface,
    AccessTokenProviderInterface
{
    public const DRIVE_FILE_SCOPE = Drive::DRIVE_FILE;

    public const SCOPE_UPGRADE_MESSAGE = 'Google access was granted with an outdated scope. Reconnect Google to keep using Drive files.';

    public const GOOGLE_SCOPES = [
        self::DRIVE_FILE_SCOPE,
        'email',
    ];

    public function __construct(
        private GoogleClientFactoryInterface $googleClientManager,
        private GoogleOAuthGatewayInterface $googleOAuthGateway,
        private OAuthCredentialProvider $oauthCredentialProvider,
        private LarpIntegrationRepository $larpIntegrationRepository,
        private GoogleSpreadsheetIntegrationHelper $googleSpreadsheetIntegrationHelper
    ) {
    }

    public static function providerKey(): string
    {
        return LarpIntegrationProvider::Google->value;
    }

    public function authorizationUrl(Larp $larp, string $state, string $redirectUri): string
    {
        return $this->googleOAuthGateway->authorizationUrl(self::GOOGLE_SCOPES, $state, $redirectUri);
    }

    public function completeConnection(Larp $larp, ConnectionCallback $callback, User $connectingUser): LarpIntegration
    {
        $authorizationCode = $callback->authorizationCode();
        if ($authorizationCode === null) {
            throw new IntegrationConnectionFailedException('Google did not return an authorization code.');
        }

        try {
            $authorization = $this->googleOAuthGateway->exchangeAuthorizationCode($authorizationCode, $callback->redirectUri);
        } catch (IdentityProviderException $exception) {
            throw new IntegrationConnectionFailedException($exception->getMessage(), 0, $exception);
        }

        return $this->upsertIntegration($larp, $authorization->accessToken, $authorization->accountEmail, $connectingUser);
    }

    public function disconnect(LarpIntegration $integration): void
    {
        $this->larpIntegrationRepository->remove($integration);
    }

    public function health(LarpIntegration $integration): IntegrationHealth
    {
        try {
            $this->accessToken($integration);
        } catch (ReAuthenticationNeededException) {
            return IntegrationHealth::needsReauth($integration->getLastError());
        }

        if (self::requiresScopeUpgrade($integration)) {
            return IntegrationHealth::needsReauth(self::SCOPE_UPGRADE_MESSAGE);
        }

        return IntegrationHealth::healthy();
    }

    public static function requiresScopeUpgrade(LarpIntegration $integration): bool
    {
        return !$integration->hasGrantedScope(self::DRIVE_FILE_SCOPE);
    }

    public function authorizationHeader(LarpIntegration $integration): string
    {
        return $this->oauthCredentialProvider->authorizationHeader($integration);
    }

    public function accessToken(LarpIntegration $integration): string
    {
        return $this->oauthCredentialProvider->accessToken($integration);
    }

    public function fetchSpreadsheetRows(SharedFile $sharedFile, ObjectFieldMapping $mapping): array
    {
        $spreadsheetMapping = SpreadsheetMappingModel::fromEntity($mapping);
        return $this->googleSpreadsheetIntegrationHelper->fetchSpreadsheetRows($sharedFile, $spreadsheetMapping);
    }

    public function fetchSpreadsheetSheetIdByName(SharedFile $sharedFile, ObjectFieldMapping $mapping): string
    {
        $spreadsheetMapping = SpreadsheetMappingModel::fromEntity($mapping);
        return $this->googleSpreadsheetIntegrationHelper->fetchSpreadsheetSheetIdByName($sharedFile, $spreadsheetMapping);
    }

    public function fetchSpreadsheetPreview(SharedFile $sharedFile, ?string $sheetName, int $headerRowNumber, int $rowLimit): SpreadsheetPreview
    {
        return $this->googleSpreadsheetIntegrationHelper->fetchSpreadsheetPreview($sharedFile, $sheetName, $headerRowNumber, $rowLimit);
    }

    public function getExternalFileUrl(LarpIntegration $integration, string $externalFileId): string
    {
        $client = $this->googleClientManager->createServiceAccountClient();
        $drive = new Drive($client);

        try {
            $file = $drive->files->get($externalFileId, ['fields' => 'webViewLink']);
            return $file->getWebViewLink();
        } catch (Exception $e) {
            throw new RuntimeException('Unable to retrieve file URL: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function createStoryObject(LarpIntegration $integration, StoryObject $storyObject): void
    {
        $mappings = $this->findMatchingFileMappingsForStoryObject($integration, $storyObject::getTargetType());

        foreach ($mappings as $mapping) {
            if ($mapping->getFileType()->isSpreadsheet()) {
                $this->createStoryObjectList($mapping, $storyObject);
            }
        }
    }

    public function removeStoryObject(LarpIntegration $integration, StoryObject $storyObject): void
    {
        throw new RuntimeException('Not implemented yet');
    }

    public function syncStoryObject(LarpIntegration $integration, StoryObject $storyObject): void
    {
        throw new RuntimeException('Not implemented yet');
    }

    public function createReferenceUrl(SharedFile $file, ReferenceType $referenceType, int|string $externalId, array $additionalData = []): ?string
    {
        $baseUrl = $file->getUrl();

        $baseUrl = preg_replace('/\?.*/', '', (string) $baseUrl);
        $sheetId = $additionalData['sheetId'] ?? 0; /* @see CharacterController::importFromSelectedMapping */
        return match ($referenceType) {
            ReferenceType::SpreadsheetRow => $baseUrl . "#gid=$sheetId&range=$externalId:$externalId",
            ReferenceType::DocumentParagraph => $baseUrl . "#heading=$externalId",
            default => $baseUrl,
        };
    }

    private function upsertIntegration(Larp $larp, AccessTokenInterface $accessToken, ?string $accountEmail, User $connectingUser): LarpIntegration
    {
        $tokenValues = $accessToken->getValues();
        $grantedScopes = $tokenValues['scope'] ?? null;
        $integration = $this->larpIntegrationRepository->findOneBy([
            'larp' => $larp,
            'provider' => LarpIntegrationProvider::Google,
        ]);
        if (!$integration instanceof LarpIntegration) {
            $integration = new LarpIntegration();
            $integration->setCreatedBy($connectingUser);
        }
        $integration->setProvider(LarpIntegrationProvider::Google);
        $integration->setConnectionType(ConnectionType::UserOauth);
        $integration->setAccessToken($accessToken->getToken());
        $integration->setRefreshToken($accessToken->getRefreshToken() ?? $integration->getRefreshToken());
        $integration->setExpiresAt($this->resolveExpiresAt($accessToken));
        $integration->setScopes($grantedScopes);
        $integration->setLarp($larp);
        $integration->setOwner($accountEmail);
        $integration->setExternalAccountId($accountEmail);
        $integration->markActive();

        $this->larpIntegrationRepository->save($integration);

        return $integration;
    }

    private function resolveExpiresAt(AccessTokenInterface $accessToken): ?\DateTimeImmutable
    {
        $expiresTimestamp = $accessToken->getExpires();
        if ($expiresTimestamp === null) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($expiresTimestamp);
    }

    /**
     * @return ObjectFieldMapping[]
     */
    private function findMatchingFileMappingsForStoryObject(LarpIntegration $integration, TargetType $targetType): array
    {
        $matching = [];

        foreach ($integration->getSharedFiles() as $sharedFile) {
            foreach ($sharedFile->getMappings() as $mapping) {
                if ($mapping->getFileType()->matchesTargetType($targetType)) {
                    $matching[] = $mapping;
                }
            }
        }

        return $matching;
    }

    private function createStoryObjectList(ObjectFieldMapping $mapping, StoryObject $storyObject): void
    {
        $sharedFile = $mapping->getExternalFile();
        Assert::notNull($sharedFile);

        $spreadsheetMapping = SpreadsheetMappingModel::fromEntity($mapping);
        $columnMapping = $spreadsheetMapping->mappings;
        $characterNameField = $columnMapping['name'] ?? null;

        if (!$characterNameField) {
            throw new RuntimeException('No "name" mapping configured.');
        }

        $rows = $this->googleSpreadsheetIntegrationHelper->fetchSpreadsheetRows($sharedFile, $spreadsheetMapping);

        foreach ($rows as $row) {
            if (isset($row[$characterNameField]) && $row[$characterNameField] === $storyObject->getTitle()) {
                throw new DuplicateStoryObjectException($storyObject, $sharedFile->getUrl());
            }
        }

        $newRow = $this->googleSpreadsheetIntegrationHelper->buildSpreadsheetRow($spreadsheetMapping, $storyObject);
        $this->googleSpreadsheetIntegrationHelper->appendRowToSpreadsheet($sharedFile, $spreadsheetMapping, $newRow);
    }
}
